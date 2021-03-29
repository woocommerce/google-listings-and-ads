<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshSyncedProducts
 *
 * Resubmits all synced WooCommerce products after first deleting them from Google Merchant Center.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class RefreshSyncedProducts extends AbstractProductSyncerBatchedJob {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'refresh_synced_products';
	}

	/**
	 * Calculate and return batch size considering target audiences per product.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		$batch_size = (int) floor( 100 / count( $this->merchant_center->get_target_countries() ) );
		// between 2 and 50 products per batch
		return min( max( $batch_size, 2 ), 50 );
	}

	/**
	 * Enqueue the "create_batch" action provided it doesn't already exist.
	 *
	 * To minimize the resource use of starting the job the batch creation is handled async.
	 *
	 * @param array $args
	 */
	public function start( array $args = [] ) {
		$args = $args[0] ?? [];
		$ids  = array_filter( $args, 'is_integer' );

		if ( ! empty( $ids ) ) {
			$this->schedule_process_action( $ids );
		} else {
			parent::start();
		}
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

		// delete the products but do not retry on failure because it will be handled by this job
		add_filter( 'gla_products_delete_retry_on_failure', '__return_false' );
		$delete_results = $this->product_syncer->delete( $products );
		remove_filter( 'gla_products_delete_retry_on_failure', '__return_false' );

		// if there were any errors while deleting, schedule a retry job for the errored products and don't submit them in the current batch
		// this will make sure we completely delete the products first before resubmitting them
		$internal_error_products = $this->batch_product_helper->get_internal_error_products( $delete_results->get_errors() );
		if ( ! empty( $internal_error_products ) ) {
			$this->start( [ $internal_error_products ] );
			$products = array_filter(
				$products,
				function ( $product ) use ( $internal_error_products ) {
					return ! in_array( $product->get_id(), $internal_error_products, true );
				}
			);
		}

		if ( ! empty( $products ) ) {
			// resubmit the successfully deleted products
			$this->product_syncer->update( $products );
		}
	}
}
