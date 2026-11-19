<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncerException;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateProducts
 *
 * Submits WooCommerce products to Google Merchant Center and/or updates the existing ones.
 *
 * Note: The job will not start if it is already running.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateProducts extends AbstractProductSyncerJob implements StartOnHookInterface {

	protected const DEFAULT_BATCH_SIZE = 25;

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_products';
	}

	/**
	 * Process an item.
	 *
	 * @param int[] $product_ids An array of WooCommerce product ids.
	 *
	 * @throws ProductSyncerException If an error occurs. The exception will be logged by ActionScheduler.
	 * @throws JobException If invalid or non-existing products are provided. The exception will be logged by ActionScheduler.
	 */
	public function process_items( array $product_ids ) {
		$args     = [ 'include' => $product_ids ];
		$products = $this->product_repository->find_sync_ready_products( $args )->get();

		if ( empty( $products ) ) {
			throw JobException::item_not_found();
		}

		$this->product_syncer->update( $products );
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args - arguments.
	 *
	 * @throws JobException If no product is provided as argument. The exception will be logged by ActionScheduler.
	 */
	public function schedule( array $args = [] ) {
		$args = $args[0] ?? [];
		$ids  = array_filter( $args, 'is_integer' );

		if ( empty( $ids ) ) {
			throw JobException::item_not_provided( 'Array of WooCommerce Product IDs' );
		}

		if ( did_action( 'woocommerce_gla_batch_retry_update_products' ) ) {
			$this->action_scheduler->schedule_single( gmdate( 'U' ) + 60, $this->get_process_item_hook(), [ $ids ] );
		} elseif ( $this->can_schedule( [ $ids ] ) ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), [ $ids ] );
		}
	}

	/**
	 * Get how many product IDs a single job can hold.
	 *
	 * @return int
	 */
	public function get_batch_size(): int {
		/**
		 * Filters the batch size for the job.
		 *
		 * @param int    $batch_size The batch size.
		 * @param string $job_name   The job's name.
		 */
		$batch_size = apply_filters( 'woocommerce_gla_batched_job_size', self::DEFAULT_BATCH_SIZE, $this->get_name() );
		$batch_size = filter_var( $batch_size, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );

		return false === $batch_size ? self::DEFAULT_BATCH_SIZE : $batch_size;
	}

	/**
	 * Get an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( 'woocommerce_gla_batch_retry_update_products', 1 );
	}
}
