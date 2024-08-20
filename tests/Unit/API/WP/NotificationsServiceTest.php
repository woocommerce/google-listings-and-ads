<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\TransformerService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationsServiceTest
 *
 * @group Notifications
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP
 */
class NotificationsServiceTest extends UnitTest {

	/**
	 * @var NotificationsService
	 */
	public $service;

	/**
	 * @var MockObject|MerchantCenterService
	 */
	public $merchant_center;

	/**
	 * @var MockObject|AccountService
	 */
	public $account;

	public const DUMMY_BLOG_ID = '123';

	// List of Topics to be used.
	public const TOPIC_PRODUCT_CREATED  = 'product.create';
	public const TOPIC_PRODUCT_DELETED  = 'product.delete';
	public const TOPIC_PRODUCT_UPDATED  = 'product.update';
	public const TOPIC_COUPON_CREATED   = 'coupon.create';
	public const TOPIC_COUPON_DELETED   = 'coupon.delete';
	public const TOPIC_COUPON_UPDATED   = 'coupon.update';
	public const TOPIC_SHIPPING_UPDATED = 'shipping.update';
	public const TOPIC_SETTINGS_UPDATED = 'settings.update';

	// Constant used to get all the allowed topics
	public const ALLOWED_TOPICS = [
		self::TOPIC_PRODUCT_CREATED,
		self::TOPIC_PRODUCT_DELETED,
		self::TOPIC_PRODUCT_UPDATED,
		self::TOPIC_COUPON_CREATED,
		self::TOPIC_COUPON_DELETED,
		self::TOPIC_COUPON_UPDATED,
		self::TOPIC_SHIPPING_UPDATED,
		self::TOPIC_SETTINGS_UPDATED,
	];

	/** @var OptionsInterface $options */
	protected $options;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		// Mock the Blog ID from Jetpack
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				if ( $name === 'id' ) {
					return self::DUMMY_BLOG_ID;
				}

				return $value;
			},
			10,
			2
		);

		add_filter( 'woocommerce_gla_notifications_enabled', '__return_true' );
	}

	/**
	 * Test if the route is correct
	 */
	public function test_route() {
		$this->service = $this->get_mock();

		$blog_id = self::DUMMY_BLOG_ID;
		$this->assertEquals( $this->service->get_notification_url(), "https://public-api.wordpress.com/wpcom/v2/sites/{$blog_id}/partners/google/notifications" );
	}


	/**
	 * Test notify() function with a call with success response.
	 */
	public function test_notify() {
		$this->service = $this->get_mock();

		$topic   = 'product.create';
		$item_id = 1;

		$args = [
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => [
				'x-woocommerce-topic' => $topic,
				'Content-Type'        => 'application/json',
			],
			'body'    => [
				'item_id' => $item_id,
			],
			'url'     => $this->service->get_notification_url(),
		];

		$this->service->expects( $this->once() )->method( 'do_request' )->with( $args )->willReturn( [ 'code' => 200 ] );
		$this->assertTrue( $this->service->notify( $topic, $item_id ) );
	}


	/**
	 * Test notify() function with a call with wp_error response.
	 */
	public function test_notify_wp_error() {
		$this->service = $this->get_mock();

		$this->service->expects( $this->once() )->method( 'do_request' )->willReturn( new \WP_Error( 'error', 'error message' ) );
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}

	/**
	 * Test notify() function with a call with an error response.
	 */
	public function test_notify_response_error() {
		$this->service = $this->get_mock();

		$this->service->expects( $this->once() )->method( 'do_request' )->willReturn(
			[
				'response' => [
					'code' => 400,
					'body' => 'Bad request',
				],
			]
		);
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}

	/**
	 * Test notify() function with valid and invalid topics
	 */
	public function test_notify_valid_and_invalid_topics() {
		$this->service = $this->get_mock();
		$this->service->expects( $this->exactly( count( self::ALLOWED_TOPICS ) ) )
			->method( 'do_request' )
			->willReturn( [ 'code' => 200 ] );

		foreach ( self::ALLOWED_TOPICS as $topic ) {
			$this->assertTrue( $this->service->notify( $topic, 1 ) );
		}

		$this->assertFalse( $this->service->notify( 'invalid.created', 1 ) );
	}

	/**
	 * Test notify() function when it is blocked by `woocommerce_gla_notify` hook
	 */
	public function test_notify_blocked_by_hook() {
		$this->service = $this->get_mock();
		$this->service->expects( $this->never() )->method( 'do_request' );

		add_filter( 'woocommerce_gla_notify', '__return_false' );
		$this->assertFalse( $this->service->notify( 'product.created', 1 ) );
		remove_filter( 'woocommerce_gla_notify', '__return_false' );
	}

	/**
	 * Test notify() function logs an error when MC is not ready for syncing
	 */
	public function test_notify_show_error_when_mc_not_ready() {
		$this->service = $this->get_mock( false );
		$this->service->expects( $this->never() )->method( 'do_request' );
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}

	/**
	 * Test notify() function logs an error when WPCOM Auth is not authorized
	 */
	public function test_notify_show_error_when_wpcom_not_authorized() {
		$this->service = $this->get_mock( true, false );
		$this->service->expects( $this->never() )->method( 'do_request' );
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}

	/**
	 * Test notify() function logs an error when disabled
	 */
	public function test_notify_show_error_when_disabled() {
		$this->service = $this->get_mock();
		remove_filter( 'woocommerce_gla_notifications_enabled', '__return_true' );
		add_filter( 'woocommerce_gla_notifications_enabled', '__return_false' );
		$this->service->expects( $this->never() )->method( 'do_request' );
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
		remove_filter( 'woocommerce_gla_notifications_enabled', '__return_false' );
	}

	/**
	 * Test notify() function logs an error when WPCOM Auth is not healthy
	 */
	public function test_notify_show_error_when_wpcom_not_healthy() {
		$this->service = $this->get_mock( true, true, false );
		$this->service->expects( $this->never() )->method( 'do_request' );
		$this->assertFalse( $this->service->notify( 'product.create', 1 ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}


	/**
	 * Mocks the service
	 *
	 * @param bool $mc_ready
	 * @param bool $wpcom_authorized
	 * @param bool $is_wpcom_api_status_healthy
	 * @return TransformerService
	 */
	public function get_mock( $mc_ready = true, $wpcom_authorized = true, $is_wpcom_api_status_healthy = true ) {
		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->merchant_center->method( 'is_ready_for_syncing' )->willReturn( $mc_ready );
		$this->account = $this->createMock( AccountService::class );
		$this->account->method( 'is_wpcom_api_status_healthy' )->willReturn( $is_wpcom_api_status_healthy );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'is_wpcom_api_authorized' )->willReturn( $wpcom_authorized );

		/** @var NotificationsService $mock */
		$mock = $this->getMockBuilder( NotificationsService::class )
			->setConstructorArgs( [ $this->merchant_center, $this->account ] )
			->onlyMethods( [ 'do_request' ] )
			->getMock();
		$mock->set_options_object( $this->options );
		return $mock;
	}
}
