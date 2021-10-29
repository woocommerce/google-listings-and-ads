<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;
use Exception;

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
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return array
	 */
	public function get_batch( int $batch_number ): array {
		return $this->get_filtered_batch( $batch_number )->get();
	}

	/**
	 * Get a single filtered batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @since 1.4.0
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return FilteredProductList
	 */
	protected function get_filtered_batch( int $batch_number ): FilteredProductList {
		return $this->product_repository->find_sync_ready_product_ids( [], $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Handles batch creation action hook.
	 *
	 * @hooked gla/jobs/{$job_name}/create_batch
	 *
	 * Schedules an action to run immediately for the items in the batch.
	 * Uses the unfiltered count to check if there are additional batches.
	 *
	 * @since 1.4.0
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @throws Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $batch_number ) {
		$create_batch_hook = $this->get_create_batch_hook();
		$create_batch_args = [ $batch_number ];

		$this->monitor->validate_failure_rate( $this, $create_batch_hook, $create_batch_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $create_batch_hook, $create_batch_args );
		}

		$items = $this->get_filtered_batch( $batch_number );

		if ( 0 === $items->get_unfiltered_count() ) {
			// if no more items the job is complete
			$this->handle_complete( $batch_number );
		} else {
			// if items, schedule the process action
			if ( count( $items ) ) {
				$this->schedule_process_action( $items->get() );
			}

			if ( $items->get_unfiltered_count() >= $this->get_batch_size() ) {
				// if there might be more items, add another "create_batch" action to handle them
				$this->schedule_create_batch_action( $batch_number + 1 );
			}
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
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
