<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\SyncerHooks;
use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\DeleteCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications\CouponNotificationJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateCoupon;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\NotificationStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;
use WC_Helper_Coupon;

/**
 * Class SyncerHooksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 */
class SyncerHooksTest extends ContainerAwareUnitTest {

	use CouponTrait;

	/** @var MockObject|MerchantCenterService $merchant_center */
	protected $merchant_center;

	/** @var MockObject|JobRepository $job_repository */
	protected $job_repository;

	/** @var MockObject|DeleteCoupon $delete_coupon_job */
	protected $delete_coupon_job;

	/** @var MockObject|UpdateCoupon $update_coupon_job */
	protected $update_coupon_job;

	/** @var MockObject|CouponNotificationJob $coupon_notification_job */
	protected $coupon_notification_job;

	/** @var MockObject|NotificationsService $notification_service */
	protected $notification_service;

	/** @var CouponHelper $coupon_helper */
	protected $coupon_helper;

	/** @var WC $wc */
	protected $wc;

	/** @var SyncerHooks $syncer_hooks */
	protected $syncer_hooks;

	public function test_create_new_simple_coupon_schedules_update_job() {
		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' );
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
		$coupon->set_code( $string_code );
		$coupon->save();
	}

	public function test_update_simple_coupon_schedules_update_job() {
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$this->coupon_helper->mark_as_synced( $coupon, 'fake_google_id', 'US' );
		$coupon->set_code( $string_code );
		$coupon->save();

		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $coupon->get_id() ] ] ) );
		$coupon->add_meta_data( 'test_coupon_field', 'testing', true );
		$coupon->save();
	}

	public function test_update_invisible_coupon_does_not_schedule_update_job() {
		$string_code = 'test_coupon_codes';
		$coupon      = new WC_Coupon();
		$coupon->update_meta_data( '_wc_gla_visibility', 'dont-sync-and-show' );
		$coupon->save_meta_data();
		$coupon->set_code( $string_code );
		$coupon->save();

		$this->update_coupon_job->expects( $this->never() )
			->method( 'schedule' );
		$coupon->add_meta_data( 'test_coupon_field', 'testing', true );
		$coupon->save();
	}

	public function test_trash_simple_coupon_schedules_delete_job() {
		$coupon = $this->create_ready_to_delete_coupon();

		$adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
		$adapted_coupon->disable_promotion( $coupon );
		$expected_coupon_entry = new DeleteCouponEntry(
			$coupon->get_id(),
			$adapted_coupon,
			[ 'US' => 'google_id' ]
		);
		$this->delete_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->callback(
					function ( $entries ) use ( $expected_coupon_entry ) {
						return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
					}
				)
			);

		wp_trash_post( $coupon->get_id() );
	}

	public function test_delete_simple_coupon_schedules_delete_job() {
		$coupon = $this->create_ready_to_delete_coupon();

		$adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
		$adapted_coupon->disable_promotion( $coupon );
		$expected_coupon_entry = new DeleteCouponEntry(
			$coupon->get_id(),
			$adapted_coupon,
			[ 'US' => 'google_id' ]
		);
		$this->delete_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with(
				$this->callback(
					function ( $entries ) use ( $expected_coupon_entry ) {
						return $entries[0]->get_wc_coupon_id() === $expected_coupon_entry->get_wc_coupon_id();
					}
				)
			);

		// force delete post
		wp_delete_post( $coupon->get_id(), true );
	}

	public function test_untrash_simple_coupon_schedules_update_job() {
		$coupon    = $this->create_ready_to_delete_coupon();
		$coupon_id = $coupon->get_id();
		$coupon->delete();

		$this->update_coupon_job->expects( $this->once() )
			->method( 'schedule' )
			->with( $this->equalTo( [ [ $coupon_id ] ] ) );

		// untrash coupon
		wp_untrash_post( $coupon_id );
	}

	public function test_modify_post_does_not_schedule_update_job() {
		$this->update_coupon_job->expects( $this->never() )
			->method( 'schedule' );

		$post = $this->factory()->post->create_and_get();
		// update post
		$this->factory()->post->update_object(
			$post->ID,
			[ 'post_title' => 'Sample title' ]
		);
		// trash post
		wp_trash_post( $post->ID );
		// un-trash post
		wp_untrash_post( $post->ID );
	}

	public function test_create_coupon_triggers_notification_created() {
		/**
		 * @var WC_Coupon $coupon
		 */
		$coupon = WC_Helper_Coupon::create_coupon( uniqid(), [ 'status' => 'draft' ] );
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );
		$this->coupon_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $coupon->get_id(),
						'topic'   => NotificationsService::TOPIC_COUPON_CREATED,
					]
				)
			);
		$coupon->set_status( 'publish' );
		$coupon->add_meta_data( '_wc_gla_visibility', ChannelVisibility::SYNC_AND_SHOW, true );
		$coupon->save();
	}

	public function test_update_coupon_triggers_notification_updated() {
		/**
		 * @var WC_Coupon $coupon
		 */
		$coupon = WC_Helper_Coupon::create_coupon( uniqid() );
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );
		$this->coupon_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $coupon->get_id(),
						'topic'   => NotificationsService::TOPIC_COUPON_UPDATED,
					]
				)
			);
		$this->coupon_helper->set_notification_status( $coupon, NotificationStatus::NOTIFICATION_CREATED );
		$coupon->set_status( 'publish' );
		$coupon->add_meta_data( '_wc_gla_visibility', ChannelVisibility::SYNC_AND_SHOW, true );
		$coupon->save();
	}

	public function test_delete_coupon_triggers_notification_delete() {
		/**
		 * @var WC_Coupon $coupon
		 */
		$coupon = WC_Helper_Coupon::create_coupon( uniqid() );
		$this->notification_service->expects( $this->once() )->method( 'is_enabled' )->willReturn( true );
		$this->coupon_notification_job->expects( $this->once() )
			->method( 'schedule' )->with(
				$this->equalTo(
					[
						'item_id' => $coupon->get_id(),
						'topic'   => NotificationsService::TOPIC_COUPON_DELETED,
					]
				)
			);
		$coupon->set_status( 'publish' );
		$coupon->add_meta_data( '_wc_gla_visibility', ChannelVisibility::DONT_SYNC_AND_SHOW, true );
		$this->coupon_helper->set_notification_status( $coupon, NotificationStatus::NOTIFICATION_UPDATED );
		$coupon->save();
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->login_as_administrator();

		$this->merchant_center = $this->createMock(
			MerchantCenterService::class
		);
		$this->merchant_center->expects( $this->any() )
			->method( 'is_ready_for_syncing' )
			->willReturn( true );

		$this->update_coupon_job       = $this->createMock( UpdateCoupon::class );
		$this->delete_coupon_job       = $this->createMock( DeleteCoupon::class );
		$this->delete_coupon_job       = $this->createMock( DeleteCoupon::class );
		$this->coupon_notification_job = $this->createMock( CouponNotificationJob::class );
		$this->notification_service    = $this->createMock( NotificationsService::class );

		$this->job_repository = $this->createMock( JobRepository::class );
		$this->job_repository->expects( $this->any() )
			->method( 'get' )
			->willReturnMap(
				[
					[ DeleteCoupon::class, $this->delete_coupon_job ],
					[ UpdateCoupon::class, $this->update_coupon_job ],
					[ CouponNotificationJob::class, $this->coupon_notification_job ],
				]
			);

		$this->wc            = $this->container->get( WC::class );
		$this->coupon_helper = $this->container->get( CouponHelper::class );
		$this->syncer_hooks  = new SyncerHooks(
			$this->coupon_helper,
			$this->job_repository,
			$this->merchant_center,
			$this->notification_service,
			$this->wc
		);

		add_filter( 'woocommerce_gla_notifications_enabled', '__return_false' );
		$this->syncer_hooks->register();
	}
}
