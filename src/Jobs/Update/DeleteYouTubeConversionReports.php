<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\ExportException;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
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
 * @since x.x.x
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
	 * DeleteYouTubeConversionReports constructor.
	 *
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param CsvExportWriter           $writer
	 */
	public function __construct( ActionSchedulerInterface $action_scheduler, ActionSchedulerJobMonitor $monitor, CsvExportWriter $writer ) {
		parent::__construct( $action_scheduler, $monitor );
		$this->writer = $writer;
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
	 * @param string[] $items Full paths to report files from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		try {
			$export_dir = $this->writer->get_export_dir();
		} catch ( ExportException $e ) {
			return;
		}

		foreach ( $items as $file_path ) {
			$filename      = basename( $file_path );
			$expected_path = trailingslashit( $export_dir ) . $filename;

			// Only touch report files sitting directly in the export folder.
			if ( $expected_path !== $file_path || ! preg_match( self::REPORT_PATTERN, $filename, $matches ) ) {
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
			}
		}
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
	 * Get the full paths of report files at the top level of the export folder, sorted by name.
	 *
	 * @return string[]
	 */
	protected function find_report_files(): array {
		try {
			$files = $this->writer->list_export_files();
		} catch ( ExportException $e ) {
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
