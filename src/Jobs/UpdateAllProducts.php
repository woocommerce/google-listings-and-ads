<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateAllProducts
 *
 * Submits all WooCommerce products to Google Merchant Center and/or updates the existing ones.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateAllProducts extends AbstractProductSyncerBatchedJob implements OptionsAwareInterface {
	use OptionsAwareTrait;
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
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->options->update( OptionsInterface::UPDATE_ALL_PRODUCTS_LAST_SYNC, strtotime( 'now' ) );
		$this->merchant_statuses->maybe_refresh_status_data( true );
	}
}
