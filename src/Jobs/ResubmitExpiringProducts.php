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
	 * Option name for the last processed post ID between batches (keyset pagination checkpoint).
	 */
	protected const RESUBMIT_EXPIRING_PRODUCTS_CHECKPOINT_OPTION = 'woocommerce_gla_resubmit_expiring_products_checkpoint';

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'resubmit_expiring_products';
	}

	/**
	 * Start the job and reset the pagination checkpoint.
	 *
	 * @param array $args Optional arguments passed from the start hook.
	 */
	public function schedule( array $args = [] ): void {
		$this->clear_checkpoint();
		parent::schedule( $args );
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number from Action Scheduler (orchestration only; pagination uses the persisted checkpoint).
	 *
	 * @return array
	 */
	public function get_batch( int $batch_number ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$after_id = (int) get_option( self::RESUBMIT_EXPIRING_PRODUCTS_CHECKPOINT_OPTION, 0 );
		$items    = $this->product_repository->find_expiring_product_ids( $this->get_batch_size(), $after_id );

		if ( ! empty( $items ) ) {
			$this->update_checkpoint( $items );
		}

		return $items;
	}

	/**
	 * Clear the pagination checkpoint when the job finishes.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 */
	protected function handle_complete( int $final_batch_number ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->clear_checkpoint();
	}

	/**
	 * Persist the highest product ID from the batch as the next keyset pagination checkpoint.
	 *
	 * @param int[] $items Product IDs from the current batch.
	 */
	protected function update_checkpoint( array $items ): void {
		$last_id = max( array_map( 'absint', $items ) );
		update_option( self::RESUBMIT_EXPIRING_PRODUCTS_CHECKPOINT_OPTION, $last_id, false );
	}

	/**
	 * Remove the stored checkpoint (job finished or a new run is starting).
	 */
	protected function clear_checkpoint(): void {
		delete_option( self::RESUBMIT_EXPIRING_PRODUCTS_CHECKPOINT_OPTION );
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
