<?php
	declare( strict_types=1 );

	namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google;

	use Automattic\WooCommerce\GoogleListingsAndAds\Google\NotificationsService;
	use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

	defined( 'ABSPATH' ) || exit;

	/**
	 * Class NotificationsServiceTest
	 *
	 * @group Notifications
	 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Google
	 */
class NotificationsServiceTest extends UnitTest {

	/**
	 * @var NotificationsService
	 */
	public $service;

	public const DUMMY_BLOG_ID = '123';

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
	}

	/**
	 * Test if the route is correct
	 */
	public function test_route() {
		$this->service = $this->get_mock( [] );

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
			],
			'body'    => [
				'item_id' => $item_id,
			],
			'url'     => $this->service->get_notification_url(),
		];

		$this->service->expects( $this->once() )->method( 'do_request' )->with( $args )->willReturn( [ 'code' => 200 ] );
		$this->assertTrue( $this->service->notify( $item_id, $topic ) );
	}


	/**
	 * Test notify() function with a call with wp_error response.
	 */
	public function test_notify_wp_error() {
		$this->service = $this->get_mock();

		$this->service->expects( $this->once() )->method( 'do_request' )->willReturn( new \WP_Error( 'error', 'error message' ) );
		$this->assertFalse( $this->service->notify( 1, 'topic' ) );
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
		$this->assertFalse( $this->service->notify( 1, 'topic' ) );
		$this->assertEquals( did_action( 'woocommerce_gla_error' ), 1 );
	}

	/**
	 * Mocks the service
	 *
	 * @return NotificationsService
	 */
	public function get_mock() {
		return $this->getMockBuilder( NotificationsService::class )
			->onlyMethods( [ 'do_request' ] )
			->getMock();
	}
}
