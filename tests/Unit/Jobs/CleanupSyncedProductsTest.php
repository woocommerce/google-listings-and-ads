<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupSyncedProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CleanupSyncedProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 *
 * @property MockObject|ActionScheduler           $action_scheduler
 * @property MockObject|ActionSchedulerJobMonitor $monitor
 * @property MockObject|ProductSyncer             $product_syncer
 * @property MockObject|ProductRepository         $product_repository
 * @property MockObject|BatchProductHelper        $batch_product_helper
 * @property MockObject|MerchantCenterService     $merchant_center
 * @property CleanupSyncedProducts                $job
 */
class CleanupSyncedProductsTest extends UnitTest {

	use ProductTrait;

	protected const JOB_NAME          = 'cleanup_synced_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';
	protected const BATCH_SIZE        = 100;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->action_scheduler     = $this->createMock( ActionScheduler::class );
		$this->monitor              = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer       = $this->createMock( ProductSyncer::class );
		$this->product_repository   = $this->createMock( ProductRepository::class );
		$this->batch_product_helper = $this->createMock( BatchProductHelper::class );
		$this->merchant_center      = $this->createMock( MerchantCenterService::class );
		$this->job                  = new CleanupSyncedProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->batch_product_helper,
			$this->merchant_center
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_one_batch() {
		$ids = [ 1, 2 ];

		$this->product_repository->expects( $this->once() )
			->method( 'find_synced_product_ids' )
			->willReturn( $ids );

		$this->action_scheduler->expects( $this->exactly( 2 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $ids ] ]
			);

		$this->job->schedule();

		// Trigger first batch
		do_action( self::CREATE_BATCH_HOOK, 1 );
	}

	public function test_schedule_multiple_batches() {
		$ids = range( 1, self::BATCH_SIZE * 5 );

		$first_batch  = array_slice( $ids, 0, self::BATCH_SIZE );
		$second_batch = array_slice( $ids, self::BATCH_SIZE, self::BATCH_SIZE );

		$this->product_repository->expects( $this->exactly( 2 ) )
			->method( 'find_synced_product_ids' )
			->will(
				$this->onConsecutiveCalls(
					$first_batch,
					$second_batch
				)
			);

		$this->action_scheduler->expects( $this->exactly( 5 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				[ self::CREATE_BATCH_HOOK, [ 1 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $first_batch ] ],
				[ self::CREATE_BATCH_HOOK, [ 2 ] ],
				[ self::PROCESS_ITEM_HOOK, [ $second_batch ] ],
				[ self::CREATE_BATCH_HOOK, [ 3 ] ]
			);

		$this->job->schedule();

		// Trigger first two batches
		do_action( self::CREATE_BATCH_HOOK, 1 );
		do_action( self::CREATE_BATCH_HOOK, 2 );
	}

	public function test_process_items() {
		$ids = [ 1, 2 ];

		$this->batch_product_helper->expects( $this->once() )
			->method( 'mark_batch_as_unsynced' )
			->with( $ids );

		do_action( self::PROCESS_ITEM_HOOK, $ids );
	}

	public function test_process_items_with_merchant_center_connected() {
		$ids = [ 1, 2 ];

		$this->merchant_center->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->batch_product_helper->expects( $this->never() )
			->method( 'mark_batch_as_unsynced' );

		do_action( self::PROCESS_ITEM_HOOK, $ids );

		$this->assertEquals( 1, did_action( 'woocommerce_gla_debug_message' ) );
	}

	public function test_merchant_center_connected() {
		$this->merchant_center->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->action_scheduler->expects( $this->never() )
			->method( 'schedule_immediate' );

		$this->job->schedule();
	}
}
