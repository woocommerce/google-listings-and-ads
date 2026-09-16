<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ResubmitExpiringProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\JobTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ResubmitExpiringProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ResubmitExpiringProductsTest extends UnitTest {

	use JobTrait;
	use ProductTrait;

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|ProductSyncer $product_syncer */
	protected $product_syncer;

	/** @var MockObject|ProductRepository $product_repository */
	protected $product_repository;

	/** @var MockObject|BatchProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|MerchantStatuses $merchant_statuses */
	protected $merchant_statuses;

	/** @var ResubmitExpiringProducts $job */
	protected $job;

	protected const JOB_NAME          = 'resubmit_expiring_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler   = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor            = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer     = $this->createMock( ProductSyncer::class );
		$this->product_repository = $this->createMock( ProductRepository::class );
		$this->product_helper     = $this->createMock( BatchProductHelper::class );
		$this->merchant_center    = $this->createMock( MerchantCenterService::class );
		$this->merchant_statuses  = $this->createMock( MerchantStatuses::class );

		$this->job = new ResubmitExpiringProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->product_helper,
			$this->merchant_center,
			$this->merchant_statuses
		);

		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( true );

		add_filter(
			'woocommerce_gla_batched_job_size',
			function ( $batch_count, $job_name ) {
				if ( self::JOB_NAME === $job_name ) {
					return 2;
				}
				return $batch_count;
			},
			10,
			2
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	/**
	 * The schedule() must enqueue the first batch with cursor 0, not batch number 1.
	 */
	public function test_schedule_starts_at_cursor_zero() {
		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::CREATE_BATCH_HOOK, [ 0 ] );

		$this->job->schedule();
	}

	/**
	 * When the first batch is empty the job must stop without scheduling a next batch.
	 */
	public function test_empty_first_batch_stops_job() {
		$this->product_repository->expects( $this->once() )
			->method( 'find_expiring_product_ids' )
			->with( 0, 2 )
			->willReturn( [] );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		// Only the initial schedule_immediate call; no further scheduling.
		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::CREATE_BATCH_HOOK, [ 0 ] );

		$this->job->schedule();
		do_action( self::CREATE_BATCH_HOOK, 0 );
	}

	/**
	 * A single partial batch (fewer items than batch size) must process its items
	 * and then schedule one more create_batch with max(ids) as the cursor.
	 */
	public function test_single_partial_batch() {
		$ids = [ 10 ];

		$this->product_repository->expects( $this->once() )
			->method( 'find_expiring_product_ids' )
			->with( 0, 2 )
			->willReturn( $ids );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->action_scheduler->expects( $this->exactly( 3 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 0 ] ],         // Initial scheduling.
				[ self::PROCESS_ITEM_HOOK, [ $ids ] ],      // Process the batch.
				[ self::CREATE_BATCH_HOOK, [ 10 ] ]         // Next cursor is the max ID.
			);

		$this->job->schedule();
		do_action( self::CREATE_BATCH_HOOK, 0 );
	}

	/**
	 * Two full batches followed by an empty batch: cursor advances correctly each time.
	 */
	public function test_multiple_full_batches_advance_cursor() {
		$batch_a = [ 10, 20 ];
		$batch_b = [ 30, 40 ];

		$this->product_repository->expects( $this->exactly( 3 ) )
			->method( 'find_expiring_product_ids' )
			->withConsecutive( [ 0, 2 ], [ 20, 2 ], [ 40, 2 ] )
			->willReturnOnConsecutiveCalls( $batch_a, $batch_b, [] );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 0 ] ],      // Initial scheduling.
				[ self::PROCESS_ITEM_HOOK, [ $batch_a ] ],
				[ self::CREATE_BATCH_HOOK, [ 20 ] ],     // Cursor advances to max of batch A.
				[ self::PROCESS_ITEM_HOOK, [ $batch_b ] ],
				[ self::CREATE_BATCH_HOOK, [ 40 ] ]      // Cursor advances to max of batch B.
			);

		$this->job->schedule();
		do_action( self::CREATE_BATCH_HOOK, 0 );
		do_action( self::CREATE_BATCH_HOOK, 20 );
		do_action( self::CREATE_BATCH_HOOK, 40 );
	}

	/**
	 * The process_items must call product_syncer->update() with the loaded WC_Product objects.
	 */
	public function test_process_items_calls_product_syncer() {
		$ids      = [ 10, 20 ];
		$products = $this->generate_simple_product_mocks_set( 2 );

		$this->product_repository->method( 'find_by_ids' )
			->with( $ids )
			->willReturn( $products );

		$this->product_syncer->expects( $this->once() )
			->method( 'update' )
			->with( $products );

		do_action( self::PROCESS_ITEM_HOOK, $ids );
	}
}
