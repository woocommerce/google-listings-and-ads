<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\AbstractItemNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Generic Class for AbstractItemNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
abstract class AbstractItemNotificationJobTest extends UnitTest {


	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var MockObject|AbstractItemNotificationJob $job */
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
		$topic = $this->get_topic_name() . '.create';
		$id    = 1;

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with(
				$this->get_process_item_hook(),
				[
					[
						'item_id' => $id,
						'topic'   => $topic,
					],
				]
			)
			->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with(
				$this->get_process_item_hook(),
				[
					[
						'item_id' => $id,
						'topic'   => $topic,
					],
				]
			);

		$this->job->schedule(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_already_scheduled() {
		$topic = $this->get_topic_name() . '.create';
		$id    = 1;

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with(
				$this->get_process_item_hook(),
				[
					[
						'item_id' => $id,
						'topic'   => $topic,
					],
				]
			)
			->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_not_enabled() {
		$topic = $this->get_topic_name() . '.create';
		$id    = 1;

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
			->method( 'has_scheduled_action' );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_process_items_calls_notify_and_set_status_on_success() {
		/** @var \WC_Product|\WC_Coupon $item */
		$item  = $this->create_item();
		$id    = $item->get_id();
		$topic = $this->get_topic_name() . '.create';

		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( $topic, $id )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->exactly( 3 ) )
			->method( 'get_wc_' . $this->get_topic_name() )
			->with( $id )
			->willReturn( $item );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'set_notification_status' )
			->with( $item, NotificationStatus::NOTIFICATION_CREATED );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_process_items_doesnt_calls_notify_when_no_args() {
		$this->notification_service->expects( $this->never() )
			->method( 'notify' );

		$this->job->handle_process_items_action( [] );
		$this->job->handle_process_items_action( [ 1 ] );
	}

	public function test_process_items_doesnt_calls_set_status_on_failure() {
		/** @var \WC_Product|\WC_Coupon $item */
		$item  = $this->create_item();
		$id    = $item->get_id();
		$topic = $this->get_topic_name() . '.create';

		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( $topic, $id )
			->willReturn( false );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'get_wc_' . $this->get_topic_name() )
			->with( $id )
			->willReturn( $item );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->never() )
			->method( 'set_notification_status' );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $topic,
			]
		);
	}

	public function test_get_after_notification_status() {
		/** @var \WC_Product|\WC_Coupon $item */
		$item = $this->create_item();
		$id   = $item->get_id();

		$this->notification_service->expects( $this->exactly( 3 ) )
			->method( 'notify' )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->exactly( 8 ) )
			->method( 'get_wc_' . $this->get_topic_name() )
			->willReturn( $item );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_update_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->exactly( 3 ) )
			->method( 'set_notification_status' )
			->willReturnCallback(
				function ( $id, $topic ) {
					if ( $topic === $this->get_topic_name() . '.create' ) {
							return NotificationStatus::NOTIFICATION_CREATED;
					} elseif ( $topic === $this->get_topic_name() . '.delete' ) {
						return NotificationStatus::NOTIFICATION_DELETED;
					} else {
						return NotificationStatus::NOTIFICATION_UPDATED;
					}
				}
			);

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.create',
			]
		);
		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.delete',
			]
		);
		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.update',
			]
		);
	}

	public function test_dont_process_item_if_status_changed() {
		/** @var \WC_Product|\WC_Coupon $item */
		$item = $this->create_item();
		$id   = $item->get_id();

		$this->notification_service->expects( $this->never() )->method( 'notify' );

		$this->job->get_helper()->expects( $this->exactly( 3 ) )
			->method( 'get_wc_' . $this->get_topic_name() )
			->willReturn( $item );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $item )
			->willReturn( false );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_update_notification' )
			->with( $item )
			->willReturn( false );

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $item )
			->willReturn( false );

		$this->job->get_helper()->expects( $this->never() )->method( 'set_notification_status' );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.create',
			]
		);
		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.delete',
			]
		);
		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.update',
			]
		);
	}

	public function test_mark_as_unsynced_when_delete() {
		$item = $this->create_item();
		$id   = $item->get_id();

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->exactly( 3 ) )
			->method( 'get_wc_' . $this->get_topic_name() )
			->with( $id )
			->willReturn( $item );

		$this->notification_service->expects( $this->once() )->method( 'notify' )->willReturn( true );
		$this->job->get_helper()->expects( $this->once() )
			->method( 'mark_as_unsynced' );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.delete',
			]
		);
	}

	public function test_mark_as_notified_when_create() {
		$item = $this->create_item();
		$id   = $item->get_id();

		$this->job->get_helper()->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $item )
			->willReturn( true );

		$this->job->get_helper()->expects( $this->exactly( 3 ) )
			->method( 'get_wc_' . $this->get_topic_name() )
			->with( $id )
			->willReturn( $item );

		$this->notification_service->expects( $this->once() )->method( 'notify' )->willReturn( true );
		$this->job->get_helper()->expects( $this->once() )
			->method( 'mark_as_notified' );

		$this->job->handle_process_items_action(
			[
				'item_id' => $id,
				'topic'   => $this->get_topic_name() . '.create',
			]
		);
	}	

	public function get_name() {
		return 'notifications/' . $this->get_job_name();
	}

	public function get_process_item_hook() {
		return 'gla/jobs/' . $this->get_name() . '/process_item';
	}

	abstract public function get_job_name();
	abstract public function get_topic_name();
	abstract public function get_job();
	abstract public function create_item();
}
