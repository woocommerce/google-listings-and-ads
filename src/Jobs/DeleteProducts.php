<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductIDRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ProductIDMap;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteProducts
 *
 * Deletes WooCommerce products from Google Merchant Center.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteProducts extends AbstractProductSyncerJob implements StartOnHookInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_products';
	}

	/**
	 * Process an item.
	 *
	 * @param string[] $product_id_map An array of Google product IDs mapped to WooCommerce product IDs as their key.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $product_id_map ) {
		$product_entries = BatchProductIDRequestEntry::create_from_id_map( new ProductIDMap( $product_id_map ) );
		$this->product_syncer->delete_by_batch_requests( $product_entries );
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule( array $args = [] ) {
		$args   = $args[0] ?? [];
		$id_map = ( new ProductIDMap( $args ) )->get();

		if ( empty( $id_map ) ) {
			throw JobException::item_not_provided( 'Array of WooCommerce product IDs' );
		}

		if ( $this->can_schedule( [ $id_map ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $id_map ] );
		}
	}

	/**
	 * Get an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( 'woocommerce_gla_batch_retry_delete_products', 1 );
	}
}
