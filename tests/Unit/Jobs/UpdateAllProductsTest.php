<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\BatchProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\FilteredProductList;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductSyncer;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\JobTrait;
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
 * @property MockObject|BatchProductHelper		  $product_helper
 * @property MockObject|MerchantCenterService     $merchant_center
 * @property UpdateAllProducts                    $job
 */
class UpdateAllProductsTest extends UnitTest {

	use ProductTrait;
	use JobTrait;

	protected const JOB_NAME          = 'update_all_products';
	protected const CREATE_BATCH_HOOK = 'gla/jobs/' . self::JOB_NAME . '/create_batch';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler      = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor               = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->product_syncer        = $this->createMock( ProductSyncer::class );
		$this->product_repository    = $this->createMock( ProductRepository::class );
		$this->product_helper        = $this->createMock( BatchProductHelper::class );
		$this->merchant_center       = $this->createMock( MerchantCenterService::class );
		$this->job                   = new UpdateAllProducts(
			$this->action_scheduler,
			$this->monitor,
			$this->product_syncer,
			$this->product_repository,
			$this->product_helper,
			$this->merchant_center
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_schedules_a_single_batched_job_with_items() {
		$items = array(
			$this->generate_simple_product_mock(),
			$this->generate_simple_product_mock(),
			$this->generate_simple_product_mock(),
			$this->generate_simple_product_mock(),
		);

		$filtered_product_list = new FilteredProductList( $items, count( $items ) );

		$this->action_scheduler
			->method( 'has_scheduled_action' )
			->willReturn( false );
		$this->action_scheduler->expects( $this->exactly( 2 ) )
			->method( 'schedule_immediate' )
			->withConsecutive(
				array( self::CREATE_BATCH_HOOK, [ 1 ] ),
				array( self::PROCESS_ITEM_HOOK, [ $filtered_product_list->get_product_ids() ] )
			);

		$this->merchant_center->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$this->product_repository->expects( $this->once() )
			->method( 'find_sync_ready_products' )
			->with( [], 100, 0 )
			->willReturn( $filtered_product_list );

		$this->job->schedule();

		do_action( self::CREATE_BATCH_HOOK, 1 );
	}
}
