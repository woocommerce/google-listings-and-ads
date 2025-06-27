<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobException;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateMerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UpdateMerchantPriceBenchmarksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class UpdateMerchantPriceBenchmarksTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor */
	protected $monitor;

	/** @var MockObject|PriceBenchmarks */
	protected $price_benchmarks;

	/** @var MockObject|MerchantCenterService $merchant_center_service */
	protected $merchant_center_service;

	/** @var UpdateMerchantPriceBenchmarks */
	protected $job;

	protected const JOB_NAME = 'update_merchant_price_benchmarks';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler        = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor                 = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->price_benchmarks        = $this->createMock( PriceBenchmarks::class );
		$this->merchant_center_service = $this->createMock( MerchantCenterService::class );

		$this->job = new UpdateMerchantPriceBenchmarks(
			$this->action_scheduler,
			$this->monitor,
			$this->merchant_center_service,
			$this->price_benchmarks
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_job_cannot_schedule_when_not_connected() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( false );

		$this->assertFalse( $this->job->can_schedule() );
	}

	public function test_job_can_schedule_when_connected() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( true );

		$this->assertTrue( $this->job->can_schedule() );
	}

	public function test_schedule_calls_action_scheduler() {
		$this->merchant_center_service->method( 'is_connected' )
			->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_recurring' )
			->with(
				time(), // timestamp
				HOUR_IN_SECONDS * 12, // interval
				'gla/jobs/' . self::JOB_NAME . '/process_item',
				[]
			);

		$this->job->schedule();
	}

	public function test_is_running_checks_action_scheduler() {
		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( 'gla/jobs/' . self::JOB_NAME . '/process_item', null )
			->willReturn( true );

		$this->assertTrue( $this->job->is_scheduled() );
	}

	public function test_process_items_updates_price_benchmarks() {
		$this->price_benchmarks->expects( $this->once() )
			->method( 'update_price_benchmarks' );

		$this->job->process_items( [] );
	}

	public function test_process_items_throws_exception_on_failure() {
		$this->price_benchmarks->method( 'update_price_benchmarks' )
			->willThrowException( new \Exception( 'API error' ) );

		$this->expectException( JobException::class );
		$this->expectExceptionMessage( 'Error updating merchant price benchmarks: API error' );

		$this->job->process_items( [] );
	}
}
