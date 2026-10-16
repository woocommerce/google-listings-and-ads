<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ResubmitExpiringProducts
 *
 * Resubmits all WooCommerce products that are nearly expired to Google Merchant Center.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class ResubmitExpiringProducts extends AbstractProductSyncerBatchedJob implements RecurringJobInterface {

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'resubmit_expiring_products';
	}

	/**
	 * Schedule the job to start, using cursor 0 so keyset pagination begins from the first product.
	 *
	 * The initial scheduling is additionally guarded to avoid starting a second concurrent run
	 * while another `create_batch` action for this job is already scheduled or running.
	 *
	 * @param array $args Optional arguments passed to the base can_schedule() logic.
	 */
	public function schedule( array $args = [] ) {
		// Respect Merchant Center readiness/enablement checks from the base class.
		if ( ! parent::can_schedule( $args ) ) {
			return;
		}

		// Prevent a second concurrent run by checking for ANY existing create_batch action.
		if ( $this->is_running( null ) ) {
			return;
		}
		$this->schedule_create_batch_action( 0 );
	}

	/**
	 * Handle the "create batch" action using keyset (cursor) pagination.
	 *
	 * The $last_id argument acts as a cursor: we fetch products with ID > $last_id, then schedule
	 * the next batch starting from the highest ID seen in this batch. This avoids the O(offset)
	 * cost of traditional OFFSET-based pagination.
	 *
	 * @hooked gla/jobs/resubmit_expiring_products/create_batch
	 *
	 * @param int $last_id The highest product ID processed so far (0 on first run).
	 *
	 * @throws \Exception If an error occurs.
	 * @throws JobException If the job failure rate is too high.
	 */
	public function handle_create_batch_action( int $last_id ) {
		$create_batch_hook = $this->get_create_batch_hook();
		$create_batch_args = [ $last_id ];

		$this->monitor->validate_failure_rate( $this, $create_batch_hook, $create_batch_args );
		if ( $this->retry_on_timeout ) {
			$this->monitor->attach_timeout_monitor( $create_batch_hook, $create_batch_args );
		}

		$items = $this->get_batch( $last_id );

		if ( empty( $items ) ) {
			$this->handle_complete( $last_id );
		} else {
			$this->schedule_process_action( $items );
			$this->schedule_create_batch_action( max( $items ) );
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $last_id The cursor: fetch products with ID strictly greater than this value.
	 *
	 * @return int[] Array of product IDs ordered ASC.
	 */
	public function get_batch( int $last_id ): array {
		return $this->product_repository->find_expiring_product_ids( $last_id, $this->get_batch_size() );
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
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval(): int {
		return 24 * 60 * 60; // 24 hours
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}
}
