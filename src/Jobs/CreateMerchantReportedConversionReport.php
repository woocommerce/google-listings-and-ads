<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder\OrderItemRowBuilder;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Class CreateMerchantReportedConversionReport
 *
 * Create a cache of Order IDs for a specific day that have a YouTube attribution source.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 * @since 2.2.0
 */
class CreateMerchantReportedConversionReport extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * @var OrderItemRowBuilder
	 */
	protected $row_builder;

	/**
	 * @var CsvExportWriter
	 */
	protected $writer;

	/**
	 * CreateMerchantReportedConversionReport constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param OrderItemRowBuilder       $row_builder
	 * @param CsvExportWriter           $writer
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, OrderItemRowBuilder $row_builder, CsvExportWriter $writer ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->row_builder = $row_builder;
		$this->writer      = $writer;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'create_youtube_merchant_reported_conversions_report';
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
	 * Get the date to create a report for.
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
		// Get the order IDs from the Options.
		$youtube_cache = $this->options->get( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] );

		// Return the current batch to process.
		return array_slice( $youtube_cache[ $this->get_date() ], $this->get_query_offset( $batch_number ), $this->get_batch_size() );
	}

	/**
	 * Process batch items.
	 *
	 * @param int[] $items A single batch of WooCommerce Order IDs from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		$filename = 'youtube-merchant-conversion-report-' . $this->get_date();

		$file = $this->writer->create_file( $filename );

		foreach ( $items as $order_id ) {
			$order = new WC_Order( $order_id );

			foreach ( $order->get_items() as $line_item ) {
				$row = $this->row_builder->build_row( $line_item );

				if ( is_array( $row ) ) {
					$this->writer->append_row( $file, $row );
				}
			}
		}
	}

	/**
	 * Called when the job is completed.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		// @TODO: Send CSV to WCS and cleanup.
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
