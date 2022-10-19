<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SyncableProductsBatchedActionSchedulerJobTrait;
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
	use SyncableProductsBatchedActionSchedulerJobTrait;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_all_products';
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

	/**
	 * Schedules a delayed batched job
	 *
	 * @param int $delay The delay time in seconds
	 */
	public function schedule_delayed( int $delay ) {
		if ( $this->can_schedule( [ 1 ] ) ) {
			$this->action_scheduler->schedule_single( gmdate( 'U' ) + $delay, $this->get_create_batch_hook(), [ 1 ] );
		}
	}

	/**
	 * Check if the current job is currently running
	 *
	 * @return bool True if the job is running.
	 */
	public function is_syncing(): bool {
		return $this->is_running( [ 1 ] );
	}
}
