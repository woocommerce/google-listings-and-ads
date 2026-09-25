<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Services\YouTubeOrders;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateYouTubeOrderIdsCache;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CreateYouTubeOrderIdsCacheTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class CreateYouTubeOrderIdsCacheTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|YouTubeOrders $youtube_orders */
	protected $youtube_orders;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var CreateYouTubeOrderIdsCache $job */
	protected $job;

	protected const TEST_DATE = '2026-01-07';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor          = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->youtube_orders   = $this->createMock( YouTubeOrders::class );
		$this->job_repository   = $this->createMock( JobRepository::class );
		$this->options          = $this->createMock( OptionsInterface::class );

		$this->job = new CreateYouTubeOrderIdsCache(
			$this->action_scheduler,
			$this->monitor,
			$this->youtube_orders,
			$this->job_repository
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

	public function test_get_batch_finds_youtube_orders() {
		$order_ids = [ 100, 101, 102 ];

		$this->youtube_orders->expects( $this->once() )
			->method( 'find_orders' )
			->with( self::TEST_DATE, 100, 0 )
			->willReturn( $order_ids );

		$batch = $this->job->get_batch( 1 );

		$this->assertEquals( $order_ids, $batch );
	}

	public function test_get_name_returns_correct_job_name() {
		$this->assertEquals(
			'create_youtube_order_ids_cache',
			$this->job->get_name()
		);
	}

	public function test_process_items_caches_order_ids() {
		$order_ids = [ 100, 101, 102 ];

		// Mock existing empty cache.
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] )
			->willReturn( [] );

		// Expect cache to be updated with new order IDs.
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::YOUTUBE_ORDER_IDS_CACHE,
				[ self::TEST_DATE => $order_ids ]
			);

		// Call process_items through reflection since it's protected.
		$method = new \ReflectionMethod( CreateYouTubeOrderIdsCache::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $this->job, $order_ids );
	}

	public function test_process_items_merges_with_existing_cache() {
		$existing_order_ids = [ 100, 101 ];
		$new_order_ids      = [ 102, 103 ];
		$expected_merged    = [ 100, 101, 102, 103 ];

		// Mock existing cache with some order IDs.
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::YOUTUBE_ORDER_IDS_CACHE, [] )
			->willReturn( [ self::TEST_DATE => $existing_order_ids ] );

		// Expect merged cache.
		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::YOUTUBE_ORDER_IDS_CACHE,
				[ self::TEST_DATE => $expected_merged ]
			);

		$method = new \ReflectionMethod( CreateYouTubeOrderIdsCache::class, 'process_items' );
		$method->setAccessible( true );
		$method->invoke( $this->job, $new_order_ids );
	}

	public function test_process_items_logs_error_on_exception() {
		$order_ids = [ 100 ];

		// Make options->get throw an exception.
		$this->options->method( 'get' )
			->willThrowException( new \Exception( 'Database connection failed' ) );

		// Track that do_action was called with the error.
		$error_logged = false;
		add_action(
			'woocommerce_gla_error',
			function ( $message, $method ) use ( &$error_logged ) {
				$error_logged = true;
				$this->assertStringContainsString( 'YouTube order IDs cache update failed', $message );
				$this->assertStringContainsString( self::TEST_DATE, $message );
				$this->assertStringContainsString( 'Database connection failed', $message );
				$this->assertEquals( 'Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CreateYouTubeOrderIdsCache::process_items', $method );
			},
			10,
			2
		);

		// Call process_items through reflection.
		$method = new \ReflectionMethod( CreateYouTubeOrderIdsCache::class, 'process_items' );
		$method->setAccessible( true );

		// Expect exception to be re-thrown after logging.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Database connection failed' );

		try {
			$method->invoke( $this->job, $order_ids );
		} finally {
			// Verify error was logged.
			$this->assertTrue( $error_logged, 'Error should be logged via do_action' );
			remove_all_actions( 'woocommerce_gla_error' );
		}
	}
}
