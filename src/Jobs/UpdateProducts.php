<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateProducts
 *
 * Submits WooCommerce products to Google Merchant Center and/or updates the existing ones.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateProducts extends AbstractProductSyncerJob implements StartOnHookInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_products';
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
		$args     = [ 'include' => $product_ids ];
		$products = $this->product_repository->find_sync_ready_products( $args );

		if ( empty( $products ) ) {
			throw JobException::item_not_found();
		}

		$this->product_syncer->update( $products );
	}

	/**
	 * Start the job.
	 *
	 * @param array[] $args
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function start( array $args = [] ) {
		$args = $args[0] ?? [];
		$ids  = array_filter( $args, 'is_integer' );

		if ( empty( $ids ) ) {
			throw JobException::item_not_provided( 'Array of WooCommerce Product IDs' );
		}

		if ( $this->can_start( [ $ids ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $ids ] );
		}
	}

	/**
	 * Get an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( 'gla_batch_retry_update_products', 1 );
	}
}
