<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SyncableProductsBatchedActionSchedulerJobTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class UpdateSyncableProductsCount
 *
 * Get the number of syncable products (i.e. product ready to be synced to Google Merchant Center) and update it in the DB.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since x.x.x
 */
class UpdateSyncableProductsCount extends AbstractBatchedActionSchedulerJob {
	use SyncableProductsBatchedActionSchedulerJobTrait;

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
