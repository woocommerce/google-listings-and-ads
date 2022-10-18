<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\JobException;
use Exception;
use WC_Product;

/*
 * Contains AbstractBatchedActionSchedulerJob methods.
 *
 * @since 2.2.0
 */
trait SyncableProductsBatchedActionSchedulerJobTrait {

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return WC_Product[]
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
		return $this->product_repository->find_sync_ready_products( [], $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
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
				$this->schedule_process_action( $items->get_product_ids() );
			}

			// Add another "create_batch" action to handle unfiltered items.
			// The last batch created here will be an empty batch, it
			// will call "handle_complete" to finish the job.
			$this->schedule_create_batch_action( $batch_number + 1 );
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
	}
}
