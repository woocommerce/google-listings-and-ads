<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder\OrderItemRowBuilder;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

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
	 * Maximum file size in bytes (10MB).
	 */
	protected const MAX_FILE_SIZE = 10485760;

	/**
	 * File size threshold to trigger new file creation (~9.5MB safety margin).
	 */
	protected const FILE_SIZE_THRESHOLD = 9961472;

	/**
	 * @var OrderItemRowBuilder
	 */
	protected $row_builder;

	/**
	 * @var CsvExportWriter
	 */
	protected $writer;

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * CreateMerchantReportedConversionReport constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param OrderItemRowBuilder       $row_builder
	 * @param CsvExportWriter           $writer
	 * @param Connection                $connection
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, OrderItemRowBuilder $row_builder, CsvExportWriter $writer, Connection $connection ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->row_builder = $row_builder;
		$this->writer      = $writer;
		$this->connection  = $connection;
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
		$date          = $this->get_date();

		// Return empty array if cache doesn't exist for this date.
		if ( ! isset( $youtube_cache[ $date ] ) || ! is_array( $youtube_cache[ $date ] ) ) {
			return [];
		}

		// Return the current batch to process.
		return array_slice( $youtube_cache[ $date ], $this->get_query_offset( $batch_number ), $this->get_batch_size() );
	}

	/**
	 * Process batch items.
	 *
	 * Handles file state persistence across batches and creates new files
	 * when the size threshold is exceeded.
	 *
	 * @param int[] $items A single batch of WooCommerce Order IDs from the get_batch() method.
	 *
	 * @throws \Exception If an error occurs during CSV creation or writing.
	 */
	protected function process_items( array $items ) {
		$date = $this->get_date();

		try {
			// Get or initialise file state.
			$export_state = $this->options->get( OptionsInterface::YOUTUBE_EXPORT_FILES, [] );
			if ( ! isset( $export_state[ $date ] ) ) {
				$export_state[ $date ] = [
					'files'        => [],
					'current_file' => '',
					'current_part' => 0,
				];
			}

			// Create first file if needed.
			if ( empty( $export_state[ $date ]['current_file'] ) ) {
				$filename  = 'youtube-merchant-conversion-report-' . $date;
				$file_path = $this->writer->create_file( $filename );

				$export_state[ $date ]['current_file'] = $file_path;
				$export_state[ $date ]['files'][]      = $file_path;

				$this->options->update( OptionsInterface::YOUTUBE_EXPORT_FILES, $export_state );
			}

			foreach ( $items as $order_id ) {
				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					continue;
				}

				// Check file size before processing this order.
				$current_file = $export_state[ $date ]['current_file'];
				$file_size    = $this->writer->get_file_size( $current_file );

				if ( $file_size >= self::FILE_SIZE_THRESHOLD ) {
					// Create new file with part suffix.
					++$export_state[ $date ]['current_part'];
					$part     = $export_state[ $date ]['current_part'];
					$filename = 'youtube-merchant-conversion-report-' . $date . '-' . $part;

					$file_path = $this->writer->create_file( $filename );

					$export_state[ $date ]['current_file'] = $file_path;
					$export_state[ $date ]['files'][]      = $file_path;

					$this->options->update( OptionsInterface::YOUTUBE_EXPORT_FILES, $export_state );
				}

				// Get items from the order.
				$line_items = $order->get_items();

				foreach ( $line_items as $line_item ) {
					$row = $this->row_builder->build_row( $line_item );

					if ( is_array( $row ) ) {
						$this->writer->append_row( $export_state[ $date ]['current_file'], $row );
					}
				}
			}
		} catch ( \Exception $e ) {
			// Log error to WooCommerce logs before re-throwing.
			do_action(
				'woocommerce_gla_error',
				sprintf(
					'YouTube merchant conversion report generation failed for %s: %s',
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
	 * Uploads generated CSV files to the WCS endpoint, then cleans up
	 * local files and cached data on success.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 *                                If equal to 1 then no items were processed by the job.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$date = $this->get_date();

		// Get file state.
		$export_state = $this->options->get( OptionsInterface::YOUTUBE_EXPORT_FILES, [] );

		if ( isset( $export_state[ $date ] ) && ! empty( $export_state[ $date ]['files'] ) ) {
			$file_paths = $export_state[ $date ]['files'];

			// Upload files to WCS endpoint.
			$results = $this->connection->upload_reports( $file_paths, $date );

			if ( $results['success'] ) {
				// Delete CSV files.
				if ( apply_filters( 'woocommerce_gla_youtube_orders_csv_delete_on_complete', true ) ) {
					foreach ( $file_paths as $file_path ) {
						$this->writer->delete_file( $file_path );
					}
				}

				// Remove file state for this date.
				unset( $export_state[ $date ] );
				$this->options->update( OptionsInterface::YOUTUBE_EXPORT_FILES, $export_state );

				// Remove order IDs cache for this date.
				$youtube_cache = $this->options->get( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] );
				if ( isset( $youtube_cache[ $date ] ) ) {
					unset( $youtube_cache[ $date ] );
					$this->options->update( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, $youtube_cache );
				}
			} else {
				// Log upload errors but don't delete files (keep for debugging).
				do_action(
					'woocommerce_gla_error',
					sprintf(
						'Conversion report upload failed: %s',
						implode( '; ', $results['errors'] )
					),
					__METHOD__
				);
			}
		}
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
