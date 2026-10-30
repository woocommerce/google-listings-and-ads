<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProcessQueuedProductUpdates
 *
 * Schedules update jobs, in batches, for products queued for a delayed update.
 *
 * Stores opt in by setting a batch window. They also get a daily run, so products stay queued no longer than a day if a scheduled run is lost.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class ProcessQueuedProductUpdates extends AbstractProductSyncerJob implements RecurringJobInterface {

	/**
	 * Number of products handled in one run before continuing in a follow-up run.
	 *
	 * Counted in products rather than batches, so a large filtered batch size doesn't make a run too heavy.
	 * A batch is never split, so one batch larger than this still runs.
	 */
	protected const MAX_PRODUCTS_PER_RUN = 500;

	/**
	 * Arguments for every run. Fixed, so the job monitor counts all runs together.
	 */
	protected const RUN_ARGS = [ [] ];

	/**
	 * @var UpdateProducts
	 */
	protected $update_products_job;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProcessQueuedProductUpdates constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param ProductSyncer             $product_syncer
	 * @param ProductRepository         $product_repository
	 * @param MerchantCenterService     $merchant_center
	 * @param UpdateProducts            $update_products_job
	 * @param ProductMetaHandler        $meta_handler
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		ProductSyncer $product_syncer,
		ProductRepository $product_repository,
		MerchantCenterService $merchant_center,
		UpdateProducts $update_products_job,
		ProductMetaHandler $meta_handler
	) {
		parent::__construct( $action_scheduler, $monitor, $product_syncer, $product_repository, $merchant_center );
		$this->update_products_job = $update_products_job;
		$this->meta_handler        = $meta_handler;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'process_queued_product_updates';
	}

	/**
	 * Get how long, in seconds, product updates wait to be sent together. 0 means they are sent right away.
	 *
	 * @return int
	 */
	public function get_batch_window(): int {
		/**
		 * Filters how long product updates wait so updates from separate requests are sent together.
		 *
		 * @param int $window The window in whole seconds, up to one hour. 0, the default, sends each request's updates right away.
		 *                    A value that is not a whole number, such as 1.5 or "60.0", also counts as 0.
		 */
		$window = apply_filters( 'woocommerce_gla_product_update_batch_window', 0 );
		$window = filter_var( $window, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] );

		return false === $window ? 0 : min( $window, HOUR_IN_SECONDS );
	}

	/**
	 * Can the job be scheduled.
	 *
	 * Only stores that set a window get the daily run. The window is checked first,
	 * so stores without one skip the parent's Action Scheduler query.
	 *
	 * @param array|null $args
	 *
	 * @return bool Returns true if the job can be scheduled.
	 */
	public function can_schedule( $args = [] ): bool {
		return $this->get_batch_window() > 0 && parent::can_schedule( $args );
	}

	/**
	 * Queue products for a delayed update, and schedule a run after the window.
	 *
	 * @param int[] $product_ids
	 */
	public function queue( array $product_ids ): void {
		foreach ( $this->product_repository->find_by_ids( $product_ids ) as $product ) {
			$this->meta_handler->update_sync_queued_at( $product, time() );
		}

		$this->schedule_delayed( $this->get_batch_window() );
	}

	/**
	 * Remove a product from the queue.
	 *
	 * Takes an ID, so it also works for a product that is being deleted.
	 *
	 * @param int $product_id
	 */
	public function dequeue( int $product_id ): void {
		delete_post_meta( $product_id, $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_QUEUED_AT ) );
	}

	/**
	 * Schedule a run right away.
	 *
	 * @param array $args Unused.
	 */
	public function schedule( array $args = [] ) {
		$this->schedule_delayed( 0 );
	}

	/**
	 * Schedule a run after the given delay, unless a run is already waiting to start.
	 *
	 * A run in progress doesn't count, because it may have already passed products queued after it started.
	 *
	 * @param int $delay The delay in seconds.
	 */
	public function schedule_delayed( int $delay ): void {
		if ( ! $this->merchant_center->is_ready_for_syncing() || $this->has_pending_run() ) {
			return;
		}

		$this->action_scheduler->schedule_single( time() + $delay, $this->get_process_item_hook(), self::RUN_ARGS );
	}

	/**
	 * Schedule one update job per batch of queued products, and remove them from the queue.
	 *
	 * Products no longer ready to sync are removed from the queue without an update job.
	 *
	 * @param array $items Unused.
	 */
	protected function process_items( array $items ) {
		// Leave the queue untouched, since the update jobs would not be scheduled.
		if ( ! $this->merchant_center->is_ready_for_syncing() ) {
			return;
		}

		$batch_size  = $this->update_products_job->get_batch_size();
		$max_batches = max( 1, intdiv( $this->get_max_products_per_run(), $batch_size ) );

		for ( $batch = 0; $batch < $max_batches; $batch++ ) {
			$product_ids = $this->product_repository->find_queued_update_ids( $batch_size );
			if ( empty( $product_ids ) ) {
				return;
			}

			$queued_at = [];
			foreach ( $this->product_repository->find_by_ids( $product_ids ) as $product ) {
				$queued_at[ $product->get_id() ] = $this->meta_handler->get_sync_queued_at( $product );
			}

			// A product can stop being ready to sync during the window, and an update job with no ready products fails.
			$ready_ids = $this->product_repository->find_sync_ready_products( [ 'include' => $product_ids ] )->get_product_ids();

			// Schedule before removing from the queue, so a failure leaves the products queued.
			if ( ! empty( $ready_ids ) ) {
				$this->update_products_job->schedule( [ $ready_ids ] );
			}

			if ( 0 === $this->remove_from_queue( $queued_at ) ) {
				return;
			}
		}

		$this->action_scheduler->schedule_immediate( $this->get_process_item_hook(), self::RUN_ARGS );
	}

	/**
	 * Remove products from the queue, keeping any product queued again since it was read.
	 *
	 * ProductMetaHandler can't delete by value, so this deletes the post meta directly.
	 *
	 * @param array $queued_at Time each product was queued, keyed by product ID.
	 *
	 * @return int The number of products removed from the queue.
	 */
	protected function remove_from_queue( array $queued_at ): int {
		$meta_key = $this->prefix_meta_key( ProductMetaHandler::KEY_SYNC_QUEUED_AT );
		$removed  = 0;

		foreach ( $queued_at as $product_id => $value ) {
			if ( null !== $value && delete_post_meta( $product_id, $meta_key, $value ) ) {
				++$removed;
			}
		}

		return $removed;
	}

	/**
	 * Get how many products one run handles before continuing in a follow-up run.
	 *
	 * @return int
	 */
	protected function get_max_products_per_run(): int {
		return self::MAX_PRODUCTS_PER_RUN;
	}

	/**
	 * Check whether a run is waiting to start.
	 *
	 * @return bool
	 */
	protected function has_pending_run(): bool {
		$pending = $this->action_scheduler->search(
			[
				'hook'     => $this->get_process_item_hook(),
				'status'   => $this->action_scheduler::STATUS_PENDING,
				'per_page' => 1,
			],
			'ids'
		);

		return ! empty( $pending );
	}

	/**
	 * Get the name of an action hook to attach the job's start method to.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}

	/**
	 * Return the recurring job's interval in seconds.
	 *
	 * @return int
	 */
	public function get_interval(): int {
		return DAY_IN_SECONDS;
	}
}
