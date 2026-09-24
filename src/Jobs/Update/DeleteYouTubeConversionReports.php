<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\AbstractBatchedActionSchedulerJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DeleteYouTubeConversionReports
 *
 * Deletes conversion report CSVs left at the top level of the export folder,
 * where they can be downloaded from the web.
 *
 * @since 3.9.5
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update
 */
class DeleteYouTubeConversionReports extends AbstractBatchedActionSchedulerJob implements TransientsAwareInterface {
	use TransientsAwareTrait;

	/**
	 * Name of the export folder inside uploads/.
	 */
	protected const EXPORT_FOLDER = 'gla-exports';

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
	 * Each processed batch removes its files from the folder, so every batch
	 * starts from the first remaining file. Files that failed to delete are
	 * excluded so the job always finishes.
	 *
	 * @param int $batch_number The batch number increments for each new batch in the job cycle.
	 *
	 * @return string[] Full paths to report files.
	 */
	public function get_batch( int $batch_number ): array {
		$failed = $this->transients->get( TransientsInterface::YOUTUBE_CLEANUP_FAILURES, [] );
		$files  = array_diff( $this->find_report_files(), (array) $failed );

		return array_slice( array_values( $files ), 0, $this->get_batch_size() );
	}

	/**
	 * Log and delete each report file in the batch.
	 *
	 * @param string[] $items Full paths to report files from the get_batch() method.
	 */
	protected function process_items( array $items ) {
		global $wp_filesystem;

		$export_dir = $this->get_export_dir();

		if ( null === $export_dir ) {
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
			if ( ! $wp_filesystem->exists( $file_path ) ) {
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

				$this->add_failure( $file_path );
			}
		}
	}

	/**
	 * Clear the list of failed deletions once the job finishes.
	 *
	 * @param int $final_batch_number The final batch number when the job was completed.
	 */
	protected function handle_complete( int $final_batch_number ) {
		$this->transients->delete( TransientsInterface::YOUTUBE_CLEANUP_FAILURES );
	}

	/**
	 * Get the full paths of report files at the top level of the export folder, sorted by name.
	 *
	 * @return string[]
	 */
	protected function find_report_files(): array {
		global $wp_filesystem;

		$export_dir = $this->get_export_dir();

		if ( null === $export_dir || ! $wp_filesystem->is_dir( $export_dir ) ) {
			return [];
		}

		$entries = $wp_filesystem->dirlist( $export_dir, false, false );
		$files   = [];

		foreach ( (array) $entries as $name => $entry ) {
			if ( 'f' === ( $entry['type'] ?? '' ) && preg_match( self::REPORT_PATTERN, (string) $name ) ) {
				$files[] = trailingslashit( $export_dir ) . $name;
			}
		}

		sort( $files );

		return $files;
	}

	/**
	 * Get the export folder path, or null when the upload directory is unavailable.
	 *
	 * @return string|null
	 */
	protected function get_export_dir(): ?string {
		$upload_dir = wp_upload_dir( null, false );

		if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
			return null;
		}

		return trailingslashit( $upload_dir['basedir'] ) . self::EXPORT_FOLDER;
	}

	/**
	 * Remember a file that failed to delete so later batches skip it.
	 *
	 * @param string $file_path Full path to the file.
	 */
	protected function add_failure( string $file_path ): void {
		$failed   = (array) $this->transients->get( TransientsInterface::YOUTUBE_CLEANUP_FAILURES, [] );
		$failed[] = $file_path;

		$this->transients->set( TransientsInterface::YOUTUBE_CLEANUP_FAILURES, array_values( array_unique( $failed ) ), DAY_IN_SECONDS );
	}
}
