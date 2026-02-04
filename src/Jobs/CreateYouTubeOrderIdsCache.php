<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Services\YouTubeOrders;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class CreateYouTubeOrderIdsCache
 *
 * Create a cache of Order IDs for a specific day that have a YouTube attribution source.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since 2.2.0
 */
class CreateYouTubeOrderIdsCache extends AbstractBatchedActionSchedulerJob implements RecurringJobInterface, OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * @var YouTubeOrders
	 */
	protected $youtube_orders;

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * CreateYouTubeOrderIdsCache constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param YouTubeOrders             $youtube_orders
	 * @param JobRepository             $job_repository
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, YouTubeOrders $youtube_orders, JobRepository $job_repository ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->youtube_orders = $youtube_orders;
		$this->job_repository = $job_repository;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'create_youtube_order_ids_cache';
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
		return apply_filters( 'woocommerce_gla_batched_job_size', 100, $this->get_name() );
	}

	/**
	 * Get the date to capture orders for.
	 *
	 * @return string
	 */
	protected function get_date(): string {
		/**
		 * Filters the YouTube orders query date value.
		 *
		 * @param string Date string formatted YYYY-MM-DD
		 */
		return apply_filters( 'woocommerce_gla_youtube_order_ids_job_date', gmdate( 'Y-m-d', strtotime( '-1 day' ) ) );
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
		return $this->youtube_orders->find_orders( $this->get_date(), $this->get_batch_size(), $this->get_query_offset( $batch_number ) );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce Order IDs from the get_batch() method.
	 *
	 * @throws \Exception If an error occurs during caching.
	 */
	protected function process_items( array $items ) {
		try {
			// Get the date for the orders.
			$date = $this->get_date();

			// Get the existing order IDs cache.
			$youtube_cache = $this->options->get( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] );

			// Create the date key if not already set.
			if ( ! isset( $youtube_cache[ $date ] ) || ! is_array( $youtube_cache[ $date ] ) ) {
				$youtube_cache[ $date ] = [];
			}

			// Update the order IDs in the option cache.
			$youtube_cache[ $date ] = array_unique( array_merge( $youtube_cache[ $date ], $items ) );

			$this->options->update( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, $youtube_cache );
		} catch ( \Exception $e ) {
			// Log error to WooCommerce logs before re-throwing.
			do_action(
				'woocommerce_gla_error',
				sprintf(
					'YouTube order IDs cache update failed for %s: %s',
					$date,
					$e->getMessage()
				),
				__METHOD__
			);

			// Re-throw so Action Scheduler marks the job as failed.
			throw $e;
		}
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		/**
		 * @var CreateMerchantReportedConversionReport
		*/
		$job = $this->job_repository->get( CreateMerchantReportedConversionReport::class );
		$job->schedule();
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
		return 24 * 60 * 60; // 24 hours
	}
}
