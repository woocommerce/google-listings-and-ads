<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteAllProducts
 *
 * Deletes all WooCommerce products from Google Merchant Center.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class DeleteAllProducts extends AbstractProductSyncerBatchedJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_all_products';
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int   $batch_number The batch number increments for each new batch in the job cycle.
	 * @param array $args Action args.
	 *
	 * @return int[]
	 */
	protected function get_batch( int $batch_number, array $args = [] ): array {
		return $this->product_repository->find_synced_product_ids( [], $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 * @param array $args Action args.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_items( array $items, array $args = [] ) {
		$products        = $this->product_repository->find_by_ids( $items );
		$product_entries = $this->batch_product_helper->generate_delete_request_entries( $products );
		$this->product_syncer->delete_by_batch_requests( $product_entries );
	}
}
