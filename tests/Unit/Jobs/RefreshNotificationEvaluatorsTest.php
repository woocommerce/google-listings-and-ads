<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshNotificationEvaluators;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshNotificationEvaluatorsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class RefreshNotificationEvaluatorsTest extends UnitTest {

	/** @var MockObject|ActionSchedulerInterface $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|NotificationService $notification_service */
	protected $notification_service;

	/** @var RefreshNotificationEvaluators $job */
	protected $job;

	protected const PROCESS_ITEM_HOOK = 'gla/jobs/refresh_notification_evaluators/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler     = $this->createMock( ActionSchedulerInterface::class );
		$this->monitor              = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->notification_service = $this->createMock( NotificationService::class );

		$this->job = new RefreshNotificationEvaluators(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service
		);
	}

	public function test_get_name() {
		$this->assertEquals( 'refresh_notification_evaluators', $this->job->get_name() );
	}

	public function test_schedule_queues_process_item() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with( self::PROCESS_ITEM_HOOK );

		$this->job->schedule();
	}

	public function test_schedule_skips_when_already_pending() {
		$this->action_scheduler->method( 'has_scheduled_action' )->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule();
	}

	public function test_process_items_refreshes_stale_evaluators() {
		$this->notification_service->expects( $this->once() )
			->method( 'refresh_stale_evaluators' );

		$this->job->handle_process_items_action( [] );
	}
}
