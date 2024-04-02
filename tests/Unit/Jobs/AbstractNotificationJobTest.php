<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\AbstractNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Generic Class for NotificationJobTests
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
abstract class AbstractNotificationJobTest extends UnitTest {

	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var AbstractNotificationJob $job */
	protected $job;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler     = $this->createMock( ActionScheduler::class );
		$this->monitor              = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->notification_service = $this->createMock( NotificationsService::class );
		$this->job                  = $this->get_job();

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( $this->get_name(), $this->job->get_name() );
	}

	public function test_schedule_schedules_immediate_job() {
		$this->notification_service->expects( $this->once() )->method( 'is_ready' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( $this->get_process_item_hook() )
			->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( $this->get_process_item_hook() );

		$this->job->schedule();
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_already_scheduled() {
		$this->notification_service->expects( $this->once() )->method( 'is_ready' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( $this->get_process_item_hook() )
			->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_not_enabled() {
		$this->notification_service->expects( $this->once() )->method( 'is_ready' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
			->method( 'has_scheduled_action' );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_process_items_calls_notify() {
		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( $this->get_topic() )
			->willReturn( true );

		$this->job->handle_process_items_action();
	}

	public function get_name() {
		return 'notifications/' . $this->get_job_name();
	}

	public function get_process_item_hook() {
		return 'gla/jobs/' . $this->get_name() . '/process_item';
	}

	abstract public function get_topic();
	abstract public function get_job_name();
	abstract public function get_job();
}
