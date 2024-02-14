<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ActionSchedulerJobMonitor;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\ProductNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;

/**
 * Class ProductNotificationJobTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Jobs
 */
class ProductNotificationJobTest extends UnitTest {


	/** @var MockObject|ActionScheduler $action_scheduler */
	protected $action_scheduler;

	/** @var MockObject|ActionSchedulerJobMonitor $monitor */
	protected $monitor;

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var ProductNotificationJob $job */
	protected $job;

	protected const JOB_NAME          = 'notifications/products';
	protected const PROCESS_ITEM_HOOK = 'gla/jobs/' . self::JOB_NAME . '/process_item';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->action_scheduler     = $this->createMock( ActionScheduler::class );
		$this->monitor              = $this->createMock( ActionSchedulerJobMonitor::class );
		$this->notification_service = $this->createMock( NotificationsService::class );
		$this->product_helper       = $this->createMock( ProductHelper::class );
		$this->job                  = new ProductNotificationJob(
			$this->action_scheduler,
			$this->monitor,
			$this->notification_service,
			$this->product_helper
		);

		$this->job->init();
	}

	public function test_job_name() {
		$this->assertEquals( self::JOB_NAME, $this->job->get_name() );
	}

	public function test_schedule_schedules_immediate_job() {
		$topic = 'product.create';
		$id    = 1;

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( self::PROCESS_ITEM_HOOK, [ $id, $topic ] )
			->willReturn( false );

		$this->action_scheduler->expects( $this->once() )
			->method( 'schedule_immediate' )
			->with(
				self::PROCESS_ITEM_HOOK,
				[ [ $id, $topic ] ]
			);

		$this->job->schedule( [ $id, $topic ] );
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_already_scheduled() {
		$id    = 1;
		$topic = 'product.create';

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );

		$this->action_scheduler->expects( $this->once() )
			->method( 'has_scheduled_action' )
			->with( self::PROCESS_ITEM_HOOK, [ $id, $topic ] )
			->willReturn( true );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule( [ $id, $topic ] );
	}

	public function test_schedule_doesnt_schedules_immediate_job_if_not_enabled() {
		$id    = 1;
		$topic = 'product.create';

		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( false );

		$this->action_scheduler->expects( $this->never() )
			->method( 'has_scheduled_action' );

		$this->action_scheduler->expects( $this->never() )->method( 'schedule_immediate' );

		$this->job->schedule( [ $id, $topic ] );
	}

	public function test_process_items_calls_notify_and_set_status_on_success() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();
		$topic   = 'product.create';

		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( $topic, $id )
			->willReturn( true );

		$this->product_helper->expects( $this->exactly( 2 ) )
		->method( 'get_wc_product' )
		->with( $id )
		->willReturn( $product );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->once() )
			->method( 'set_notification_status' )
			->with( $product, NotificationStatus::NOTIFICATION_CREATED );

		$this->job->handle_process_items_action( [ $id, $topic ] );
	}

	public function test_process_items_doesnt_calls_notify_when_no_args() {
		$this->notification_service->expects( $this->never() )
			->method( 'notify' );

		$this->job->handle_process_items_action( [] );
		$this->job->handle_process_items_action( [ 1 ] );
	}

	public function test_process_items_doesnt_calls_set_status_on_failure() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();
		$topic   = 'product.create';

		$this->notification_service->expects( $this->once() )
			->method( 'notify' )
			->with( $topic, $id )
			->willReturn( false );

		$this->product_helper->expects( $this->once() )
			->method( 'get_wc_product' )
			->with( $id )
			->willReturn( $product );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->never() )
			->method( 'set_notification_status' );

		$this->job->handle_process_items_action( [ $id, $topic ] );
	}

	public function test_get_after_notification_status() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();

		$this->notification_service->expects( $this->exactly( 3 ) )
			->method( 'notify' )
			->willReturn( true );

		$this->product_helper->expects( $this->exactly( 6 ) )
			->method( 'get_wc_product' )
			->willReturn( $product );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_update_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $product )
			->willReturn( true );

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'set_notification_status' )
			->willReturnCallback(
				function ( $id, $topic ) {
					if ( $topic === 'product.create' ) {
							return NotificationStatus::NOTIFICATION_CREATED;
					} elseif ( $topic === 'product.delete' ) {
						return NotificationStatus::NOTIFICATION_DELETED;
					} else {
						return NotificationStatus::NOTIFICATION_UPDATED;
					}
				}
			);

		$this->job->handle_process_items_action( [ $id, 'product.create' ] );
		$this->job->handle_process_items_action( [ $id, 'product.delete' ] );
		$this->job->handle_process_items_action( [ $id, 'product.update' ] );
	}

	public function test_dont_process_item_if_status_changed() {
		/** @var \WC_Product $product */
		$product = WC_Helper_Product::create_simple_product();
		$id      = $product->get_id();

		$this->notification_service->expects( $this->never() )->method( 'notify' );

		$this->product_helper->expects( $this->exactly( 3 ) )
			->method( 'get_wc_product' )
			->willReturn( $product );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_create_notification' )
			->with( $product )
			->willReturn( false );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_update_notification' )
			->with( $product )
			->willReturn( false );

		$this->product_helper->expects( $this->once() )
			->method( 'should_trigger_delete_notification' )
			->with( $product )
			->willReturn( false );

		$this->product_helper->expects( $this->never() )->method( 'set_notification_status' );

		$this->job->handle_process_items_action( [ $id, 'product.create' ] );
		$this->job->handle_process_items_action( [ $id, 'product.delete' ] );
		$this->job->handle_process_items_action( [ $id, 'product.update' ] );
	}
}
