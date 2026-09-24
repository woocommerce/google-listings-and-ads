<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Update\DeleteYouTubeConversionReports;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

/**
 * Class DeleteYouTubeConversionReportsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs\Update
 */
class DeleteYouTubeConversionReportsTest extends UnitTest {

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var array $transient_store */
	protected $transient_store = [];

	/** @var string $upload_dir */
	protected $upload_dir;

	/** @var string $export_dir */
	protected $export_dir;

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

		$this->transients = $this->createMock( TransientsInterface::class );
		$this->transients->method( 'get' )->willReturnCallback(
			function ( $name, $default_value = null ) {
				return $this->transient_store[ $name ] ?? $default_value;
			}
		);
		$this->transients->method( 'set' )->willReturnCallback(
			function ( $name, $value ) {
				$this->transient_store[ $name ] = $value;
				return true;
			}
		);
		$this->transients->method( 'delete' )->willReturnCallback(
			function ( $name ) {
				unset( $this->transient_store[ $name ] );
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

	public function test_each_batch_starts_from_the_first_remaining_file() {
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
		$this->process_items( $first );

		$second = $this->job->get_batch( 2 );
		$this->assertSame( [ $files[2] ], $second );
		$this->process_items( $second );

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

	public function test_failed_deletion_is_logged_skipped_and_cleared_on_complete() {
		$file = $this->create_file( 'youtube-merchant-conversion-report-2026-01-07.csv' );

		$writer = $this->createMock( CsvExportWriter::class );
		$writer->method( 'delete_file' )->willReturn( false );
		$job = $this->create_job( $writer );

		$errors = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$errors ) {
				$errors[] = $message;
			}
		);

		$this->process_items( [ $file ], $job );

		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( 'youtube-merchant-conversion-report-2026-01-07.csv', $errors[0] );
		$this->assertFileExists( $file );
		$this->assertSame( [], $job->get_batch( 2 ), 'A file that failed to delete should not be batched again.' );

		$complete = new ReflectionMethod( DeleteYouTubeConversionReports::class, 'handle_complete' );
		$complete->setAccessible( true );
		$complete->invoke( $job, 2 );

		$this->assertArrayNotHasKey( TransientsInterface::YOUTUBE_CLEANUP_FAILURES, $this->transient_store );
	}

	/**
	 * Create a job instance using the given writer.
	 *
	 * @param CsvExportWriter $writer
	 *
	 * @return DeleteYouTubeConversionReports
	 */
	protected function create_job( CsvExportWriter $writer ): DeleteYouTubeConversionReports {
		$job = new DeleteYouTubeConversionReports(
			$this->createMock( ActionSchedulerInterface::class ),
			$this->createMock( ActionSchedulerJobMonitor::class ),
			$writer
		);
		$job->set_transients_object( $this->transients );

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
