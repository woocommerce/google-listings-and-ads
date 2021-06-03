<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupProductsJob
 *
 * Deletes all stale Google products from Google Merchant Center.
 * Stale products are the ones that are no longer relevant due to a change in merchant settings.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CleanupProductsJob extends AbstractProductSyncerBatchedJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'cleanup_products_job';
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
		return $this->product_repository->find_synced_product_ids( $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 */
	protected function process_items( array $items ) {
		$products      = $this->product_repository->find_by_ids( $items );
		$stale_entries = $this->batch_product_helper->generate_stale_products_request_entries( $products );
		$this->product_syncer->delete_by_batch_requests( $stale_entries );
	}
}
