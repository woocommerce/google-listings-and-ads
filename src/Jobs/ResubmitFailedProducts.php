<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchInvalidProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ResubmitFailedProducts
 *
 * Resubmits the WooCommerce products that have failed syncing to the Google Merchant Center.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class ResubmitFailedProducts extends AbstractProductSyncerJob implements StartOnHookInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'resubmit_failed_products';
	}

	/**
	 * Init the job.
	 *
	 * The job name is used to generate the schedule event name.
	 */
	public function init(): void {
		add_action( $this->get_process_item_hook(), [ $this, 'process_items' ], 10, 1 );
	}

	/**
	 * Process an item.
	 *
	 * @param int[] $product_ids An array of WooCommerce product ids.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 * @throws JobException If invalid or non-existing products are provided. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $product_ids ) {
		$products = $this->product_repository->find_by_ids( $product_ids );

		if ( empty( $products ) ) {
			throw JobException::item_not_found();
		}

		$this->product_syncer->update( $products );
	}

	/**
	 * Start the job.
	 *
	 * @param BatchInvalidProductEntry[] $args An array of invalid product entries returned by the batch update process.
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function start( array $args = [] ) {
		if ( empty( $args ) ) {
			throw JobException::item_not_provided( 'Array of batch invalid product entries' );
		}

		$args = $this->get_internal_error_products( $args );

		if ( ! empty( $args ) && $this->can_start( $args ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $args ] );
		}
	}

	/**
	 * Filters the arguments and returns an array of WooCommerce product IDs with internal errors
	 *
	 * @param BatchInvalidProductEntry[] $args
	 *
	 * @return int[] An array of WooCommerce product ids.
	 */
	protected function get_internal_error_products( array $args ): array {
		$internal_error_ids = [];
		foreach ( $args as $invalid_product ) {
			$product_id = $invalid_product->get_wc_product_id();
			$errors     = $invalid_product->get_errors();
			if ( ! empty( $errors[ GoogleProductService::INTERNAL_ERROR_REASON ] ) ) {
				$internal_error_ids[ $product_id ] = $product_id;
			}
		}

		return $internal_error_ids;
	}

	/**
	 * Get an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( 'gla_batch_update_products_errors', 1 );
	}
}
