<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateMerchantReportedConversionReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\DeleteYouTubeConversionReports;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * Class DeleteYouTubeConversionReportsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update
 */
class DeleteYouTubeConversionReportsTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var array $option_store */
	protected $option_store = [];

	/** @var string $upload_dir */
	protected $upload_dir;

	/** @var string $export_dir */
	protected $export_dir;

	/** @var string|null $in_progress_date Date the report job is building, or null when it's idle. */
	protected $in_progress_date = null;

	/** @var DeleteYouTubeConversionReports $job */
	protected $job;

	public function setUp(): void {
		parent::setUp();

		$this->upload_dir = sys_get_temp_dir() . '/gla-test-uploads-' . uniqid();
		$this->export_dir = $this->upload_dir . '/gla-exports';
		wp_mkdir_p( $this->export_dir );

		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['basedir'] = $this->upload_dir;
				return $dirs;
			}
		);

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get' )->willReturnCallback(
			function ( $name, $default_value = null ) {
				return $this->option_store[ $name ] ?? $default_value;
			}
		);
		$this->options->method( 'update' )->willReturnCallback(
			function ( $name, $value ) {
				$this->option_store[ $name ] = $value;
				return true;
			}
		);
		$this->options->method( 'delete' )->willReturnCallback(
			function ( $name ) {
				unset( $this->option_store[ $name ] );
				return true;
			}
		);

		$this->job = $this->create_job( new CsvExportWriter() );
	}

	public function tearDown(): void {
		global $wp_filesystem;

		$wp_filesystem->rmdir( $this->upload_dir, true );

		remove_all_filters( 'upload_dir' );
		remove_all_filters( 'woocommerce_gla_batched_job_size' );
		remove_all_actions( 'woocommerce_gla_debug_message' );
		remove_all_actions( 'woocommerce_gla_error' );

		parent::tearDown();
	}

	public function test_get_batch_returns_only_loose_report_files() {
		$report   = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );
		$part     = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07-1.csv' );
		$earliest = $this->create_file( 'youtube-merchant-conversion-report-2026-01-06.csv' );

		$this->create_file( 'index.html' );
		$this->create_file( 'other-export.csv' );
		$this->create_file( 'youtube-merchant-conversion-report-2026-01-07.txt' );
		$this->create_file( 'youtube-merchant-conversion-report-latest.csv' );
		$this->create_file( 'abc123/youtube-merchant-conversion-report-2026-01-08.csv' );
		wp_mkdir_p( $this->export_dir . '/youtube-merchant-conversion-report-2026-01-09.csv' );

		$this->assertSame( [ $earliest, $part, $report ], $this->job->get_batch( 1 ) );
	}

	public function test_get_batch_returns_empty_when_export_folder_is_missing() {
		global $wp_filesystem;

		$wp_filesystem->rmdir( $this->export_dir, true );

		$this->assertSame( [], $this->job->get_batch( 1 ) );
	}

	public function test_get_batch_logs_exception_and_returns_empty_when_upload_dir_errors() {
		$this->add_upload_dir_error();

		$this->assertSame( [], $this->job->get_batch( 1 ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_batches_take_consecutive_slices_of_the_list_saved_by_the_first_batch() {
		add_filter(
			'woocommerce_gla_batched_job_size',
			function () {
				return 2;
			}
		);

		$files = [
			$this->create_file( 'youtube-merchant-conversion-report-2026-01-05.csv' ),
			$this->create_file( 'youtube-merchant-conversion-report-2026-01-06.csv' ),
			$this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' ),
		];

		$first = $this->job->get_batch( 1 );
		$this->assertSame( [ $files[0], $files[1] ], $first );
		$this->assertSame( $files, $this->option_store[ OptionsInterface::YOUTUBE_REPORT_CLEANUP_FILES ] );

		// A file added after the job started isn't picked up.
		$this->create_file( 'youtube-merchant-conversion-report-2026-01-08.csv' );

		// The next batch doesn't overlap, even before the first one is processed.
		$this->assertSame( [ $files[2] ], $this->job->get_batch( 2 ) );

		$this->process_items( $first );

		$this->assertSame( [ $files[2] ], $this->job->get_batch( 2 ) );
		$this->assertSame( [], $this->job->get_batch( 3 ) );
	}

	public function test_process_items_logs_each_file_then_deletes_it() {
		$file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07-2.csv', 'order_id,item' );

		$logged = [];
		add_action(
			'woocommerce_gla_debug_message',
			function ( $message ) use ( &$logged, $file ) {
				$logged[] = [ $message, file_exists( $file ) ];
			}
		);

		$this->process_items( [ $file ] );

		$this->assertCount( 1, $logged );
		$this->assertStringContainsString( 'youtube-merchant-conversion-report-2026-01-07-2.csv', $logged[0][0] );
		$this->assertStringContainsString( 'date 2026-01-07', $logged[0][0] );
		$this->assertStringContainsString( '13 bytes', $logged[0][0] );
		$this->assertTrue( $logged[0][1], 'The file should still exist when it is logged.' );
		$this->assertFileDoesNotExist( $file );
	}

	public function test_process_items_skips_files_outside_the_top_level_or_not_matching() {
		$nested = $this->create_file( 'abc123/youtube-merchant-conversion-report-2026-01-08.csv' );
		$other  = $this->create_file( 'other-export.csv' );

		$this->process_items( [ $nested, $other ] );

		$this->assertFileExists( $nested );
		$this->assertFileExists( $other );
	}

	public function test_process_items_skips_files_tracked_by_an_in_progress_report() {
		$part         = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );
		$current_file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07-1.csv' );
		$leftover     = $this->create_file( 'youtube-merchant-conversion-report-2026-01-06.csv' );

		$in_progress_state = [
			'files'        => [ $part, $current_file ],
			'current_file' => $current_file,
			'current_part' => 1,
		];

		$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] = [ '2026-01-07' => $in_progress_state ];
		$this->in_progress_date                                       = '2026-01-07';

		$this->process_items( [ $leftover, $part, $current_file ] );

		$this->assertFileDoesNotExist( $leftover );
		$this->assertFileExists( $part );
		$this->assertFileExists( $current_file );
		$this->assertSame(
			[ '2026-01-07' => $in_progress_state ],
			$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ]
		);
	}

	public function test_process_items_deletes_tracked_files_and_state_when_report_job_is_idle() {
		$part         = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );
		$current_file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07-1.csv' );

		$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] = [
			'2026-01-07' => [
				'files'        => [ $part, $current_file ],
				'current_file' => $current_file,
				'current_part' => 1,
			],
		];

		$this->process_items( [ $part, $current_file ] );

		$this->assertFileDoesNotExist( $part );
		$this->assertFileDoesNotExist( $current_file );
		$this->assertSame( [], $this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] );
	}

	public function test_process_items_deletes_files_and_state_from_other_dates_while_a_report_is_in_progress() {
		$failed      = $this->create_file( 'youtube-merchant-conversion-report-2026-01-06.csv' );
		$in_progress = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );

		$in_progress_state = [
			'files'        => [ $in_progress ],
			'current_file' => $in_progress,
			'current_part' => 0,
		];

		$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] = [
			'2026-01-06' => [
				'files'        => [ $failed ],
				'current_file' => $failed,
				'current_part' => 0,
			],
			'2026-01-07' => $in_progress_state,
		];
		$this->in_progress_date = '2026-01-07';

		$this->process_items( [ $failed, $in_progress ] );

		$this->assertFileDoesNotExist( $failed );
		$this->assertFileExists( $in_progress );
		$this->assertSame(
			[ '2026-01-07' => $in_progress_state ],
			$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ]
		);
	}

	public function test_process_items_keeps_state_when_deletion_fails() {
		$file  = $this->create_file( 'youtube-merchant-conversion-report-2026-01-06.csv' );
		$state = [
			'2026-01-06' => [
				'files'        => [ $file ],
				'current_file' => $file,
				'current_part' => 0,
			],
		];

		$this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] = $state;

		$writer = $this->getMockBuilder( CsvExportWriter::class )
			->onlyMethods( [ 'delete_file' ] )
			->getMock();
		$writer->method( 'delete_file' )->willReturn( false );

		$this->process_items( [ $file ], $this->create_job( $writer ) );

		$this->assertFileExists( $file );
		$this->assertSame( $state, $this->option_store[ OptionsInterface::YOUTUBE_EXPORT_FILES ] );
	}

	public function test_process_items_logs_exception_and_keeps_files_when_upload_dir_errors() {
		$file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );
		$this->add_upload_dir_error();

		$this->process_items( [ $file ] );

		$this->assertFileExists( $file );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_exception' ) );
	}

	public function test_second_run_is_a_no_op() {
		$file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );

		$this->process_items( $this->job->get_batch( 1 ) );
		$this->assertFileDoesNotExist( $file );

		$errors = 0;
		add_action(
			'woocommerce_gla_error',
			function () use ( &$errors ) {
				++$errors;
			}
		);

		$this->assertSame( [], $this->job->get_batch( 1 ) );
		$this->process_items( [ $file ] );

		$this->assertSame( 0, $errors );
	}

	public function test_failed_deletion_is_logged_and_not_batched_again() {
		$file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );

		$writer = $this->getMockBuilder( CsvExportWriter::class )
			->onlyMethods( [ 'delete_file' ] )
			->getMock();
		$writer->method( 'delete_file' )->willReturn( false );
		$job = $this->create_job( $writer );

		$errors = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$errors ) {
				$errors[] = $message;
			}
		);

		$this->process_items( $job->get_batch( 1 ), $job );

		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( 'youtube-merchant-conversion-report-2026-01-07.csv', $errors[0] );
		$this->assertFileExists( $file );
		$this->assertSame( [], $job->get_batch( 2 ), 'A file that failed to delete should not be batched again.' );
	}

	public function test_handle_complete_removes_the_saved_list() {
		$this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );
		$this->job->get_batch( 1 );

		$complete = new ReflectionMethod( DeleteYouTubeConversionReports::class, 'handle_complete' );
		$complete->setAccessible( true );
		$complete->invoke( $this->job, 2 );

		$this->assertArrayNotHasKey( OptionsInterface::YOUTUBE_REPORT_CLEANUP_FILES, $this->option_store );
	}

	/**
	 * Create a job instance using the given writer.
	 *
	 * @param CsvExportWriter $writer
	 *
	 * @return DeleteYouTubeConversionReports
	 */
	protected function create_job( CsvExportWriter $writer ): DeleteYouTubeConversionReports {
		$report_job = $this->createMock( CreateMerchantReportedConversionReport::class );
		$report_job->method( 'get_in_progress_date' )->willReturnCallback(
			function () {
				return $this->in_progress_date;
			}
		);

		$job = new DeleteYouTubeConversionReports(
			$this->createMock( ActionSchedulerInterface::class ),
			$this->createMock( ActionSchedulerJobMonitor::class ),
			$writer,
			$report_job
		);
		$job->set_options_object( $this->options );

		return $job;
	}

	/**
	 * Create a file inside the export folder.
	 *
	 * @param string $relative_path Path relative to the export folder.
	 * @param string $contents      File contents.
	 *
	 * @return string Full path to the file.
	 */
	protected function create_file( string $relative_path, string $contents = '' ): string {
		global $wp_filesystem;

		$path = $this->export_dir . '/' . $relative_path;
		wp_mkdir_p( dirname( $path ) );
		$wp_filesystem->put_contents( $path, $contents );

		return $path;
	}

	/**
	 * Point the uploads base folder somewhere missing, so the writer can't resolve the export folder.
	 */
	protected function add_upload_dir_error(): void {
		add_filter(
			'upload_dir',
			function ( $dirs ) {
				$dirs['basedir'] = $this->upload_dir . '/missing';
				return $dirs;
			},
			20
		);
	}

	/**
	 * Run the protected process_items() method.
	 *
	 * @param string[]                            $items
	 * @param DeleteYouTubeConversionReports|null $job
	 */
	protected function process_items( array $items, ?DeleteYouTubeConversionReports $job = null ): void {
		$method = new ReflectionMethod( DeleteYouTubeConversionReports::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $job ?? $this->job, $items );
	}
}
