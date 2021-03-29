<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateAllProducts
 *
 * Submits all WooCommerce products to Google Merchant Center and/or updates the existing ones.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateAllProducts extends AbstractProductSyncerBatchedJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_all_products';
	}

	/**
	 * Calculate and return batch size considering target audiences per product.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		$batch_size = (int) floor( 200 / count( $this->merchant_center->get_target_countries() ) );
		// between 2 and 50 products per batch
		return min( max( $batch_size, 2 ), 50 );
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return array
	 */
	public function get_batch( int $batch_number ): array {
		return $this->product_repository->find_sync_ready_product_ids( [], $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_items( array $items ) {
		$products = $this->product_repository->find_by_ids( $items );

		$this->product_syncer->update( $products );
	}
}
