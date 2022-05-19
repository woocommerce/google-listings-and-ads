<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Class CleanupSyncedProducts
 *
 * Marks products as unsynced when we disconnect the Merchant Account.
 * The Merchant Center must remain disconnected during the job. If it is
 * reconnected during the job it will stop processing, since the
 * ProductSyncer will take over and update all the products.
 *
 * @since 1.12.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CleanupSyncedProducts extends AbstractProductSyncerBatchedJob {

	/**
	 * Get whether Merchant Center is connected.
	 *
	 * @return bool
	 */
	public function is_mc_connected(): bool {
		return $this->merchant_center->is_connected();
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'cleanup_synced_products';
	}

	/**
	 * Can the job be scheduled.
	 * Only cleanup when the Merchant Center is disconnected.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return ! $this->is_running( $args ) && ! $this->is_mc_connected();
	}

	/**
	 * Get a single batch of items.
	 *
	 * If no items are returned the job will stop.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return int[]
	 */
	public function get_batch( int $batch_number ): array {
		return $this->product_repository->find_synced_product_ids( [], $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Process batch items.
	 * Skips processing if the Merchant Center has been connected again.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		if ( $this->is_mc_connected() ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Skipping cleanup of unsynced products because Merchant Center is connected: %s',
					implode( ',', $items )
				),
				__METHOD__
			);
			return;
		}

		$this->batch_product_helper->mark_batch_as_unsynced( $items );
	}
}
