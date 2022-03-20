<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateProductsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 *
 * @property MockObject|ActionScheduler           $action_scheduler
 * @property MockObject|ActionSchedulerJobMonitor $monitor
 * @property MockObject|ProductSyncer             $product_syncer
 * @property MockObject|ProductRepository         $product_repository
 * @property MockObject|MerchantCenterService     $merchant_center
 * @property UpdateProducts                       $job
 */
class UpdateProductsTest extends UnitTest {

	use ProductTrait;

	protected const JOB_NAME          = 'update_products';
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
		$this->merchant_center      = $this->createMock( MerchantCenterService::class );
		$this->job                  = new UpdateProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->merchant_center
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_throws_exception_no_args() {
		$this->expectException( JobException::class );

		$this->job->schedule();
	}

	public function test_schedule_schedules_single_job() {
		$ids = [ 1, 2 ];

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_single' )
			->with(
				$this->greaterThan( 0 ),
				self::PROCESS_ITEM_HOOK,
				[ $ids ]
			);

		do_action( 'woocommerce_gla_batch_retry_update_products', [] );

		$this->job->schedule( [ $ids ] );
	}

	public function test_schedule_schedules_immediate_job() {
		$ids = [ 1, 2 ];

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );

		$this->merchant_center
			->method( 'is_connected' )
			->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with(
				self::PROCESS_ITEM_HOOK,
				[ $ids ]
			);
		$this->job->schedule( [ $ids ] );
	}
}
