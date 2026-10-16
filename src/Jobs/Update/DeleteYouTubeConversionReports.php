<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\ExportException;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateMerchantReportedConversionReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteYouTubeConversionReports
 *
 * Deletes conversion report CSVs left at the top level of the export folder,
 * where they can be downloaded from the web.
 *
 * @since n.e.x.t
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update
 */
class DeleteYouTubeConversionReports extends AbstractBatchedActionSchedulerJob implements OptionsAwareInterface {
	use OptionsAwareTrait;

	/**
	 * Matches a report file name and captures its date and optional part number.
	 */
	protected const REPORT_PATTERN = '/^youtube-merchant-conversion-report-(\d{4}-\d{2}-\d{2})(?:-(\d+))?\.csv$/';

	/**
	 * @var CsvExportWriter
	 */
	protected $writer;

	/**
	 * @var CreateMerchantReportedConversionReport
	 */
	protected $report_job;

	/**
	 * DeleteYouTubeConversionReports constructor.
	 *
	 * @param ActionSchedulerInterface               $action_scheduler
	 * @param ActionSchedulerJobMonitor              $monitor
	 * @param CsvExportWriter                        $writer
	 * @param CreateMerchantReportedConversionReport $report_job
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, CsvExportWriter $writer, CreateMerchantReportedConversionReport $report_job ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->writer     = $writer;
		$this->report_job = $report_job;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_youtube_conversion_reports';
	}

	/**
	 * Get a single batch of report files to delete.
	 *
	 * The first batch lists the report files and saves the list. Every batch
	 * then takes its slice of that saved list, so batches never overlap and a
	 * file that fails to delete isn't batched again.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return string[] Full paths to report files.
	 */
	public function get_batch( int $batch_number ): array {
		if ( 1 === $batch_number ) {
			$this->options->update( OptionsInterface::YOUTUBE_REPORT_CLEANUP_FILES, $this->find_report_files() );
		}

		$files = (array) $this->options->get( OptionsInterface::YOUTUBE_REPORT_CLEANUP_FILES, [] );

		return array_slice( $files, $this->get_query_offset( $batch_number ), $this->get_batch_size() );
	}

	/**
	 * Log and delete each report file in the batch.
	 *
	 * Files for the date the report job is still building are skipped, so that
	 * report isn't uploaded with missing rows. The report job deletes them itself
	 * once it uploads successfully. Any other date's saved report state, such as
	 * one left behind by a failed upload, is removed along with its files.
	 *
	 * @param string[] $items Full paths to report files from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		try {
			$export_dir = $this->writer->get_export_dir();
		} catch ( ExportException $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return;
		}

		$in_progress_date = $this->report_job->get_in_progress_date();
		$in_use           = $this->get_in_use_report_files( $in_progress_date );
		$deleted_dates    = [];

		foreach ( $items as $file_path ) {
			$filename      = basename( $file_path );
			$expected_path = trailingslashit( $export_dir ) . $filename;

			// Only touch report files sitting directly in the export folder.
			if ( $expected_path !== $file_path || ! preg_match( self::REPORT_PATTERN, $filename, $matches ) ) {
				continue;
			}

			if ( isset( $in_use[ $file_path ] ) ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Skipping conversion report %s, it is still in use by the in-progress report for %s.', $filename, $in_progress_date ),
					__METHOD__
				);
				continue;
			}

			// Already removed, e.g. by an earlier run.
			if ( ! $this->writer->file_exists( $file_path ) ) {
				continue;
			}

			$size = $this->writer->get_file_size( $file_path );

			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Deleting leftover conversion report %s (date %s, %d bytes).',
					$filename,
					$matches[1],
					$size
				),
				__METHOD__
			);

			if ( ! $this->writer->delete_file( $file_path ) ) {
				do_action(
					'woocommerce_gla_error',
					sprintf( 'Failed to delete leftover conversion report %s.', $filename ),
					__METHOD__
				);
				continue;
			}

			$deleted_dates[ $matches[1] ] = true;
		}

		$this->remove_report_state( array_keys( $deleted_dates ), $in_progress_date );
	}

	/**
	 * Remove the saved file list once the job finishes.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->options->delete( OptionsInterface::YOUTUBE_REPORT_CLEANUP_FILES );
	}

	/**
	 * Get the files tracked for the date the report job is still building, keyed by full path.
	 *
	 * @param string|null $in_progress_date Date the report job is building, or null when it's idle.
	 *
	 * @return array<string, true>
	 */
	protected function get_in_use_report_files( ?string $in_progress_date ): array {
		if ( null === $in_progress_date ) {
			return [];
		}

		$export_state = (array) $this->options->get( OptionsInterface::YOUTUBE_EXPORT_FILES, [] );
		$state        = (array) ( $export_state[ $in_progress_date ] ?? [] );
		$paths        = (array) ( $state['files'] ?? [] );
		$paths[]      = $state['current_file'] ?? '';
		$in_use       = [];

		foreach ( $paths as $path ) {
			if ( is_string( $path ) && '' !== $path ) {
				$in_use[ $path ] = true;
			}
		}

		return $in_use;
	}

	/**
	 * Remove the saved report state for dates whose files were deleted.
	 *
	 * The report job only clears a date's state after a successful upload, so
	 * state from a failed upload would otherwise stay forever.
	 *
	 * @param string[]    $dates            Dates whose files were deleted.
	 * @param string|null $in_progress_date Date the report job is building, which is never removed.
	 */
	protected function remove_report_state( array $dates, ?string $in_progress_date ): void {
		$export_state = (array) $this->options->get( OptionsInterface::YOUTUBE_EXPORT_FILES, [] );
		$remove       = array_intersect_key( $export_state, array_flip( $dates ) );

		unset( $remove[ $in_progress_date ?? '' ] );

		if ( empty( $remove ) ) {
			return;
		}

		do_action(
			'woocommerce_gla_debug_message',
			sprintf( 'Removing saved conversion report state for %s.', implode( ', ', array_keys( $remove ) ) ),
			__METHOD__
		);

		$this->options->update( OptionsInterface::YOUTUBE_EXPORT_FILES, array_diff_key( $export_state, $remove ) );
	}

	/**
	 * Get the full paths of report files at the top level of the export folder, sorted by name.
	 *
	 * @return string[]
	 */
	protected function find_report_files(): array {
		try {
			$files = $this->writer->list_export_files();
		} catch ( ExportException $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			return [];
		}

		return array_values(
			array_filter(
				$files,
				function ( string $file_path ): bool {
					return (bool) preg_match( self::REPORT_PATTERN, basename( $file_path ) );
				}
			)
		);
	}
}
