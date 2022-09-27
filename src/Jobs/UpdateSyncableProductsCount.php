<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\SyncableProductsBatchedActionSchedulerJobTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
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
class UpdateSyncableProductsCount extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;
	use SyncableProductsBatchedActionSchedulerJobTrait;

	/**
	 * @var ProductRepository
	 */
	protected $product_repository;

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
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, ProductRepository $product_repository ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->product_repository = $product_repository;
		$this->count              = 0;
	}

	/**
	 * Get the count instance variable.
	 *
	 * @return int
	 */
	public function get_count(): int {
		return $this->count;
	}

	/**
	 * Set the count instance variable.
	 *
	 * @param int $count The number that will be set to $this->count.
	 */
	public function set_count( $count ) {
		$this->count = $count;
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
		$this->set_count( $this->get_count() + count( $items ) );
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->options->update( OptionsInterface::SYNCABLE_PRODUCTS_COUNT, $this->get_count() );
	}
}
