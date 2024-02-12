<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ProductNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ShippingNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class ShippingNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ShippingNotificationJobTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var ProductNotificationJob $job */
	protected $job;

	protected const JOB_NAME          = 'notifications/shipping';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler     = $this->createMock( ActionScheduler::class );
		$this->monitor              = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->notification_service = $this->createMock( NotificationsService::class );
		$this->job                  = new ShippingNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_schedules_immediate_job() {
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( self::PROCESS_ITEM_HOOK, [] )
			->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK );

		$this->job->schedule();
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_already_scheduled() {
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( self::PROCESS_ITEM_HOOK, [] )
			->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_not_enabled() {
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
			->method( 'has_scheduled_action' );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_process_items_calls_notify() {
		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( NotificationsService::TOPIC_SHIPPING_UPDATED )
			->willReturn( true );

		$this->job->handle_process_items_action();
	}
}
