<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\RowBuilder\OrderItemRowBuilder;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Writer\CsvExportWriter;
use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateMerchantReportedConversionReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CreateMerchantReportedConversionReportTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CreateMerchantReportedConversionReportTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|OrderItemRowBuilder $row_builder */
	protected $row_builder;

	/** @var MockObject|CsvExportWriter $writer */
	protected $writer;

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var CreateMerchantReportedConversionReport $job */
	protected $job;

	protected const TEST_DATE = '2026-01-07';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->row_builder      = $this->createMock( OrderItemRowBuilder::class );
		$this->writer           = $this->createMock( CsvExportWriter::class );
		$this->connection       = $this->createMock( Connection::class );
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->job = new CreateMerchantReportedConversionReport(
			$this->action_scheduler,
			$this->monitor,
			$this->row_builder,
			$this->writer,
			$this->connection
		);

		$this->job->set_options_object( $this->options );

		// Override date for testing.
		add_filter(
			'woocommerce_gla_youtube_order_ids_job_date',
			function () {
				return self::TEST_DATE;
			}
		);
	}

	public function tearDown(): void {
		remove_all_filters( 'woocommerce_gla_youtube_order_ids_job_date' );
		parent::tearDown();
	}

	public function test_get_batch_returns_order_ids_from_cache() {
		$order_ids = [ 100, 101, 102, 103, 104 ];

		$this->options->method( 'get' )
			->with( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] )
			->willReturn( [ self::TEST_DATE => $order_ids ] );

		$batch = $this->job->get_batch( 1 );

		$this->assertEquals( $order_ids, $batch );
	}

	public function test_get_batch_returns_empty_when_no_cache() {
		$this->options->method( 'get' )
			->with( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] )
			->willReturn( [] );

		$batch = $this->job->get_batch( 1 );

		$this->assertEquals( [], $batch );
	}

	public function test_handle_complete_uploads_files_and_cleans_up_on_success() {
		$file_paths = [
			'/path/to/youtube-merchant-conversion-report-2026-01-07.csv',
			'/path/to/youtube-merchant-conversion-report-2026-01-07-1.csv',
		];

		$export_state = [
			self::TEST_DATE => [
				'files'        => $file_paths,
				'current_file' => $file_paths[1],
				'current_part' => 1,
			],
		];

		$youtube_cache = [
			self::TEST_DATE => [ 100, 101, 102 ],
		];

		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::YOUTUBE_EXPORT_FILES, [], $export_state ],
					[ OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [], $youtube_cache ],
				]
			);

		// Successful upload.
		$this->connection->expects( $this->once() )
			->method( 'upload_reports' )
			->with( $file_paths, self::TEST_DATE )
			->willReturn(
				[
					'success'  => true,
					'uploaded' => 2,
					'failed'   => 0,
					'errors'   => [],
				]
			);

		// Should delete files.
		$this->writer->expects( $this->exactly( 2 ) )
			->method( 'delete_file' );

		// Should update options (remove date entries).
		$update_count = 0;
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_count ) {
					++$update_count;
					if ( 1 === $update_count ) {
						$this->assertEquals( OptionsInterface::YOUTUBE_EXPORT_FILES, $key );
						$this->assertArrayNotHasKey( self::TEST_DATE, $value );
					}
					if ( 2 === $update_count ) {
						$this->assertEquals( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, $key );
						$this->assertArrayNotHasKey( self::TEST_DATE, $value );
					}
					return true;
				}
			);

		// Call handle_complete through reflection since it's protected.
		$method = new \ReflectionMethod( CreateMerchantReportedConversionReport::class, 'handle_complete' );
		$method->setAccessible( true );
		$method->invoke( $this->job, 5 );
	}

	public function test_handle_complete_retains_files_on_upload_failure() {
		$file_paths = [ '/path/to/report.csv' ];

		$export_state = [
			self::TEST_DATE => [
				'files'        => $file_paths,
				'current_file' => $file_paths[0],
				'current_part' => 0,
			],
		];

		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::YOUTUBE_EXPORT_FILES, [], $export_state ],
				]
			);

		// Failed upload.
		$this->connection->expects( $this->once() )
			->method( 'upload_reports' )
			->willReturn(
				[
					'success'  => false,
					'uploaded' => 0,
					'failed'   => 1,
					'errors'   => [ 'Connection error' ],
				]
			);

		// Should NOT delete files.
		$this->writer->expects( $this->never() )
			->method( 'delete_file' );

		// Should NOT update export state or cache (no cleanup).
		$this->options->expects( $this->never() )
			->method( 'update' );

		$method = new \ReflectionMethod( CreateMerchantReportedConversionReport::class, 'handle_complete' );
		$method->setAccessible( true );
		$method->invoke( $this->job, 5 );
	}

	public function test_handle_complete_respects_filter_when_deletion_disabled() {
		$file_paths = [
			'/path/to/youtube-merchant-conversion-report-2026-01-07.csv',
			'/path/to/youtube-merchant-conversion-report-2026-01-07-1.csv',
		];

		$export_state = [
			self::TEST_DATE => [
				'files'        => $file_paths,
				'current_file' => $file_paths[1],
				'current_part' => 1,
			],
		];

		$youtube_cache = [
			self::TEST_DATE => [ 100, 101, 102 ],
		];

		$this->options->method( 'get' )
			->willReturnMap(
				[
					[ OptionsInterface::YOUTUBE_EXPORT_FILES, [], $export_state ],
					[ OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [], $youtube_cache ],
				]
			);

		// Successful upload.
		$this->connection->expects( $this->once() )
			->method( 'upload_reports' )
			->with( $file_paths, self::TEST_DATE )
			->willReturn(
				[
					'success'  => true,
					'uploaded' => 2,
					'failed'   => 0,
					'errors'   => [],
				]
			);

		// Add filter to disable deletion.
		add_filter(
			'woocommerce_gla_youtube_orders_csv_delete_on_complete',
			'__return_false'
		);

		// Should NOT delete files when filter returns false.
		$this->writer->expects( $this->never() )
			->method( 'delete_file' );

		// Should still update options (remove date entries).
		$update_count = 0;
		$this->options->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->willReturnCallback(
				function ( $key, $value ) use ( &$update_count ) {
					++$update_count;
					if ( 1 === $update_count ) {
						$this->assertEquals( OptionsInterface::YOUTUBE_EXPORT_FILES, $key );
						$this->assertArrayNotHasKey( self::TEST_DATE, $value );
					}
					if ( 2 === $update_count ) {
						$this->assertEquals( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, $key );
						$this->assertArrayNotHasKey( self::TEST_DATE, $value );
					}
					return true;
				}
			);

		// Call handle_complete through reflection since it's protected.
		$method = new \ReflectionMethod( CreateMerchantReportedConversionReport::class, 'handle_complete' );
		$method->setAccessible( true );
		$method->invoke( $this->job, 5 );

		// Clean up filter.
		remove_filter(
			'woocommerce_gla_youtube_orders_csv_delete_on_complete',
			'__return_false'
		);
	}

	public function test_get_name_returns_correct_job_name() {
		$this->assertEquals(
			'create_youtube_merchant_reported_conversions_report',
			$this->job->get_name()
		);
	}

	public function test_process_items_logs_error_on_exception() {
		$order_ids = [ 100 ];

		// Set up empty export state.
		$this->options->method( 'get' )
			->with( OptionsInterface::YOUTUBE_EXPORT_FILES, [] )
			->willReturn( [] );

		// Make writer throw an exception when creating file.
		$this->writer->method( 'create_file' )
			->willThrowException( new \Exception( 'Failed to create CSV file: Permission denied' ) );

		// Track that do_action was called with the error.
		$error_logged = false;
		add_action(
			'woocommerce_gla_error',
			function ( $message, $method ) use ( &$error_logged ) {
				$error_logged = true;
				$this->assertStringContainsString( 'YouTube merchant conversion report generation failed', $message );
				$this->assertStringContainsString( self::TEST_DATE, $message );
				$this->assertStringContainsString( 'Permission denied', $message );
				$this->assertEquals( 'Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateMerchantReportedConversionReport::process_items', $method );
			},
			10,
			2
		);

		// Call process_items through reflection since it's protected.
		$method = new \ReflectionMethod( CreateMerchantReportedConversionReport::class, 'process_items' );
		$method->setAccessible( true );

		// Expect exception to be re-thrown after logging.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Failed to create CSV file: Permission denied' );

		try {
			$method->invoke( $this->job, $order_ids );
		} finally {
			// Verify error was logged.
			$this->assertTrue( $error_logged, 'Error should be logged via do_action' );
			remove_all_actions( 'woocommerce_gla_error' );
		}
	}

	public function test_process_items_logs_error_when_options_update_fails() {
		$order_ids = [ 100 ];

		// Make options->get work initially.
		$this->options->method( 'get' )
			->with( OptionsInterface::YOUTUBE_EXPORT_FILES, [] )
			->willReturn( [] );

		// Make create_file succeed but options->update throw exception.
		$this->writer->method( 'create_file' )
			->willReturn( '/path/to/report.csv' );

		$this->options->method( 'update' )
			->willThrowException( new \Exception( 'Failed to update options in database' ) );

		// Track that error was logged.
		$error_logged = false;
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$error_logged ) {
				$error_logged = true;
				$this->assertStringContainsString( 'YouTube merchant conversion report generation failed', $message );
				$this->assertStringContainsString( 'Failed to update options in database', $message );
			},
			10,
			2
		);

		$method = new \ReflectionMethod( CreateMerchantReportedConversionReport::class, 'process_items' );
		$method->setAccessible( true );

		$this->expectException( \Exception::class );

		try {
			$method->invoke( $this->job, $order_ids );
		} finally {
			$this->assertTrue( $error_logged, 'Options update error should be logged' );
			remove_all_actions( 'woocommerce_gla_error' );
		}
	}
}
