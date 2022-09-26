<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Exception;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateSyncableProductsCount
 *
 * Get the number of syncable products (i.e. product ready to be synced to Google Merchant Center) and update it in the DB.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class UpdateSyncableProductsCount extends AbstractBatchedActionSchedulerJob {

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * @var int
	 */
	private $count;

	/**
	 * UpdateSyncableProductsCount constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductRepository         $product_repository
	 * @param TransientsInterface       $transients
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ProductRepository $product_repository, TransientsInterface $transients ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->product_repository = $product_repository;
		$this->transients         = $transients;
		$this->count              = 0;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'update_syncable_products_count';
	}

	/**
	 * Get job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size(): int {
		/**
		 * Filters the batch size for the job.
		 *
		 * @param string Job's name
		 */
		return apply_filters( 'woocommerce_gla_batched_job_size', 500, $this->get_name() );
	}

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
			$this->schedule_create_batch_action( $batch_number + 1 );
		}

		$this->monitor->detach_timeout_monitor( $create_batch_hook, $create_batch_args );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce product IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		$this->count += count( $items );
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->transients->set( TransientsInterface::SYNCABLE_PRODUCTS_COUNT, $this->count, HOUR_IN_SECONDS );
	}
}
