<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProcessQueuedProductUpdates;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\JobTrait;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProcessQueuedProductUpdatesTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ProcessQueuedProductUpdatesTest extends UnitTest {

	use JobTrait;

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|UpdateProducts $update_products_job */
	protected $update_products_job;

	/** @var ProductMetaHandler $meta_handler */
	protected $meta_handler;

	/** @var ProcessQueuedProductUpdates $job */
	protected $job;

	protected const JOB_NAME          = 'process_queued_product_updates';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';
	protected const START_HOOK        = 'gla/jobs/' . self::JOB_NAME . '/start';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler    = $this->createMock( ActionScheduler::class );
		$this->monitor             = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_repository  = $this->createMock( ProductRepository::class );
		$this->merchant_center     = $this->createMock( MerchantCenterService::class );
		$this->update_products_job = $this->createMock( UpdateProducts::class );
		$this->meta_handler        = new ProductMetaHandler();

		$this->job = new ProcessQueuedProductUpdates( ...$this->get_job_arguments() );

		$this->update_products_job->method( 'get_batch_size' )->willReturn( 2 );

		$this->job->init();
		$this->add_job_start_hook();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_delayed_schedules_one_run_after_the_delay() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'search' )
			->with(
				[
					'hook'     => self::PROCESS_ITEM_HOOK,
					'status'   => ActionSchedulerInterface::STATUS_PENDING,
					'per_page' => 1,
				],
				'ids'
			)
			->willReturn( [] );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_single' )
			->with(
				$this->callback(
					function ( $timestamp ) {
						return abs( time() + 60 - $timestamp ) <= 1;
					}
				),
				self::PROCESS_ITEM_HOOK,
				[ [] ]
			);

		$this->job->schedule_delayed( 60 );
	}

	public function test_schedule_delayed_skips_when_a_run_is_pending() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->action_scheduler->method( 'search' )->willReturn( [ 123 ] );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_single' );

		$this->job->schedule_delayed( 60 );
	}

	public function test_schedule_delayed_ignores_a_run_in_progress() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		// A run in progress makes has_scheduled_action() true, but it isn't pending.
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );
		$this->action_scheduler->method( 'search' )->willReturn( [] );

		$this->action_scheduler->expects( $this->once() )->method( 'schedule_single' );

		$this->job->schedule_delayed( 60 );
	}

	public function test_schedule_delayed_skips_when_merchant_center_is_not_ready() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( false );
		$this->action_scheduler->method( 'search' )->willReturn( [] );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_single' );

		$this->job->schedule_delayed( 60 );
	}

	public function test_start_hook_schedules_a_run_right_away() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->action_scheduler->method( 'search' )->willReturn( [] );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_single' )
			->with(
				$this->callback(
					function ( $timestamp ) {
						return abs( time() - $timestamp ) <= 1;
					}
				),
				self::PROCESS_ITEM_HOOK,
				[ [] ]
			);

		do_action( self::START_HOOK );
	}

	public function test_get_interval_is_one_day() {
		$this->assertEquals( DAY_IN_SECONDS, $this->job->get_interval() );
	}

	public function test_get_window_defaults_to_zero() {
		$this->assertSame( 0, $this->job->get_window() );
	}

	/**
	 * @dataProvider window_values_provider
	 *
	 * @param mixed $filtered_value
	 * @param int   $expected
	 */
	public function test_get_window_validates_the_filtered_value( $filtered_value, int $expected ) {
		add_filter(
			'woocommerce_gla_product_update_batch_window',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);

		$this->assertSame( $expected, $this->job->get_window() );
	}

	/**
	 * @return array
	 */
	public function window_values_provider(): array {
		return [
			'valid integer'    => [ 60, 60 ],
			'numeric string'   => [ '120', 120 ],
			'zero'             => [ 0, 0 ],
			'negative'         => [ -30, 0 ],
			'not a number'     => [ 'abc', 0 ],
			'float'            => [ 1.5, 0 ],
			'above one hour'   => [ 7200, HOUR_IN_SECONDS ],
			'exactly one hour' => [ HOUR_IN_SECONDS, HOUR_IN_SECONDS ],
		];
	}

	public function test_can_schedule_is_false_without_a_window() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_can_schedule_is_true_with_a_window() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		add_filter( 'woocommerce_gla_product_update_batch_window', fn() => 60 );

		$this->assertTrue( $this->job->can_schedule() );
	}

	public function test_can_schedule_is_false_when_merchant_center_is_not_ready() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( false );
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );
		add_filter( 'woocommerce_gla_product_update_batch_window', fn() => 60 );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_queue_marks_products_queued_and_schedules_a_run_after_the_window() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->action_scheduler->method( 'search' )->willReturn( [] );
		add_filter( 'woocommerce_gla_product_update_batch_window', fn() => 60 );

		$product_1 = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		$this->mock_repository_with_queue( [ $product_1, $product_2 ] );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_single' )
			->with(
				$this->callback(
					function ( $timestamp ) {
						return abs( time() + 60 - $timestamp ) <= 1;
					}
				),
				self::PROCESS_ITEM_HOOK,
				[ [] ]
			);

		$this->job->queue( [ $product_1->get_id(), $product_2->get_id() ] );

		$this->assertEqualsWithDelta( time(), $this->get_queued_at( $product_1 ), 1 );
		$this->assertEqualsWithDelta( time(), $this->get_queued_at( $product_2 ), 1 );
	}

	public function test_dequeue_removes_the_product_from_the_queue() {
		$product = $this->create_queued_product( 1000 );

		$this->job->dequeue( $product->get_id() );

		$this->assertNull( $this->get_queued_at( $product ) );
	}

	public function test_process_leaves_the_queue_when_merchant_center_is_not_ready() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( false );
		$product = $this->create_queued_product( 1000 );

		$this->product_repository->expects( $this->never() )->method( 'find_queued_update_ids' );
		$this->update_products_job->expects( $this->never() )->method( 'schedule' );

		do_action( self::PROCESS_ITEM_HOOK, [] );

		$this->assertEquals( 1000, $this->get_queued_at( $product ) );
	}

	public function test_process_stops_when_the_queue_is_empty() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->product_repository->method( 'find_queued_update_ids' )->willReturn( [] );

		$this->update_products_job->expects( $this->never() )->method( 'schedule' );
		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		do_action( self::PROCESS_ITEM_HOOK, [] );
	}

	public function test_process_schedules_one_update_job_per_batch_and_clears_the_queue() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$product_1 = $this->create_queued_product( 1000 );
		$product_2 = $this->create_queued_product( 1000 );
		$product_3 = $this->create_queued_product( 1000 );
		$this->mock_repository_with_queue( [ $product_1, $product_2, $product_3 ] );

		$this->update_products_job->expects( $this->exactly( 2 ) )
			->method( 'schedule' )
			->withConsecutive(
				[ [ [ $product_1->get_id(), $product_2->get_id() ] ] ],
				[ [ [ $product_3->get_id() ] ] ]
			);
		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		do_action( self::PROCESS_ITEM_HOOK, [] );

		$this->assertNull( $this->get_queued_at( $product_1 ) );
		$this->assertNull( $this->get_queued_at( $product_2 ) );
		$this->assertNull( $this->get_queued_at( $product_3 ) );
	}

	public function test_process_keeps_a_product_queued_again_while_it_runs() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$product = $this->create_queued_product( 1000 );
		$this->mock_repository_with_queue( [ $product ] );

		// The product is saved and queued again after the run read it, but before the queue is cleared.
		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->willReturnCallback(
				function () use ( $product ) {
					$this->meta_handler->update_sync_queued_at( wc_get_product( $product->get_id() ), 2000 );
				}
			);

		do_action( self::PROCESS_ITEM_HOOK, [] );

		$this->assertEquals( 2000, $this->get_queued_at( $product ) );
	}

	public function test_process_keeps_the_queue_when_scheduling_fails() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		$product = $this->create_queued_product( 1000 );
		$this->mock_repository_with_queue( [ $product ] );

		$this->update_products_job->method( 'schedule' )
			->willThrowException( JobException::item_not_provided( 'Array of WooCommerce Product IDs' ) );

		// The failed run is rescheduled by the base job.
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK, [ [] ] );

		try {
			do_action( self::PROCESS_ITEM_HOOK, [] );
			$this->fail( 'The scheduling error should be thrown again.' );
		} catch ( JobException $exception ) {
			$this->assertEquals( 1000, $this->get_queued_at( $product ) );
		}
	}

	public function test_process_continues_in_a_follow_up_run_at_the_product_limit() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->use_product_limit( 4 );

		$products = $this->create_queued_products( 6 );
		$this->mock_repository_with_queue( $products );

		// A limit of 4 products with a batch size of 2 allows 2 batches.
		$this->update_products_job->expects( $this->exactly( 2 ) )->method( 'schedule' );
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK, [ [] ] );

		do_action( self::PROCESS_ITEM_HOOK, [] );

		$this->assertNull( $this->get_queued_at( $products[3] ) );
		$this->assertEquals( 1000, $this->get_queued_at( $products[4] ) );
		$this->assertEquals( 1000, $this->get_queued_at( $products[5] ) );
	}

	public function test_process_runs_one_batch_larger_than_the_product_limit() {
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );
		$this->use_product_limit( 1 );

		$products = $this->create_queued_products( 3 );
		$this->mock_repository_with_queue( $products );

		$this->update_products_job->expects( $this->once() )
			->method( 'schedule' )
			->with( [ [ $products[0]->get_id(), $products[1]->get_id() ] ] );
		$this->action_scheduler->expects( $this->once() )->method( 'schedule_immediate' );

		do_action( self::PROCESS_ITEM_HOOK, [] );

		$this->assertEquals( 1000, $this->get_queued_at( $products[2] ) );
	}

	/**
	 * Replace the job under test with one that handles fewer products per run.
	 *
	 * @param int $limit
	 */
	protected function use_product_limit( int $limit ): void {
		remove_all_actions( self::PROCESS_ITEM_HOOK );

		$this->job = $this->getMockBuilder( ProcessQueuedProductUpdates::class )
			->setConstructorArgs( $this->get_job_arguments() )
			->onlyMethods( [ 'get_max_products_per_run' ] )
			->getMock();
		$this->job->method( 'get_max_products_per_run' )->willReturn( $limit );
		$this->job->init();
	}

	/**
	 * @return array
	 */
	protected function get_job_arguments(): array {
		return [
			$this->action_scheduler,
			$this->monitor,
			$this->createMock( ProductSyncer::class ),
			$this->product_repository,
			$this->merchant_center,
			$this->update_products_job,
			$this->meta_handler,
		];
	}

	/**
	 * @param int $count
	 *
	 * @return WC_Product[]
	 */
	protected function create_queued_products( int $count ): array {
		$products = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$products[] = $this->create_queued_product( 1000 );
		}

		return $products;
	}

	/**
	 * Create a product queued for a delayed update.
	 *
	 * @param int $queued_at
	 *
	 * @return WC_Product
	 */
	protected function create_queued_product( int $queued_at ): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		$this->meta_handler->update_sync_queued_at( $product, $queued_at );

		return $product;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return int|null
	 */
	protected function get_queued_at( WC_Product $product ): ?int {
		$value = $this->meta_handler->get_sync_queued_at( wc_get_product( $product->get_id() ) );

		return null === $value ? null : (int) $value;
	}

	/**
	 * Mock the repository so it reads the queue from the given products' meta, like the real query.
	 *
	 * @param WC_Product[] $products
	 */
	protected function mock_repository_with_queue( array $products ): void {
		$this->product_repository->method( 'find_queued_update_ids' )->willReturnCallback(
			function ( int $limit ) use ( $products ) {
				$queued = array_filter(
					$products,
					function ( WC_Product $product ) {
						return null !== $this->get_queued_at( $product );
					}
				);

				return array_slice( array_map( fn( WC_Product $product ) => $product->get_id(), array_values( $queued ) ), 0, $limit );
			}
		);

		$this->product_repository->method( 'find_by_ids' )->willReturnCallback(
			function ( array $ids ) {
				return array_map( 'wc_get_product', $ids );
			}
		);
	}
}
