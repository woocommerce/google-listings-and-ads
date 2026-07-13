<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\NotificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test suite for NotificationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers
 * @group Notifications
 */
class NotificationControllerTest extends RESTControllerUnitTest {

	protected const ROUTE        = '/wc/gla/notifications';
	protected const ROUTE_DELETE = '/wc/gla/notifications/test_notification';

	protected const TEST_NOTIFICATIONS = [
		[
			'id'           => 'test_notification',
			'triggered_at' => 1700000000,
		],
		[
			'id'           => 'another_notification',
			'triggered_at' => 1700000100,
		],
	];

	/**
	 * @var MockObject|NotificationService
	 */
	private $service;

	/**
	 * @var NotificationController
	 */
	protected $controller;

	public function setUp(): void {
		parent::setUp();
		$this->service    = $this->createMock( NotificationService::class );
		$this->controller = new NotificationController( $this->server, $this->service );
		$this->controller->register();
	}

	public function test_register_routes() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE . '/(?P<id>[a-zA-Z0-9_-]+)', $this->server->get_routes() );
	}

	public function test_get_notifications_route() {
		$this->service->expects( $this->once() )
			->method( 'get_notifications' )
			->willReturn( self::TEST_NOTIFICATIONS );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'notifications' => self::TEST_NOTIFICATIONS ], $response->get_data() );
	}

	public function test_delete_notification_route() {
		$this->service->expects( $this->once() )
			->method( 'has' )
			->with( 'test_notification' )
			->willReturn( true );

		$this->service->expects( $this->once() )
			->method( 'dismiss' )
			->with( 'test_notification' );

		$this->service->expects( $this->once() )
			->method( 'get_notifications' )
			->willReturn( [ self::TEST_NOTIFICATIONS[1] ] );

		$response = $this->do_request( self::ROUTE_DELETE, 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'notifications' => [ self::TEST_NOTIFICATIONS[1] ] ], $response->get_data() );
	}

	public function test_delete_notification_invalid_id() {
		$this->service->expects( $this->never() )
			->method( 'has' );

		$this->service->expects( $this->never() )
			->method( 'dismiss' );

		$response = $this->do_request( self::ROUTE . '/$$$', 'DELETE' );

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_delete_notification_unknown_id() {
		$this->service->expects( $this->once() )
			->method( 'has' )
			->with( 'this_id_never_existed' )
			->willReturn( false );

		$this->service->expects( $this->never() )
			->method( 'dismiss' );

		$this->service->expects( $this->never() )
			->method( 'get_notifications' );

		$response = $this->do_request( self::ROUTE . '/this_id_never_existed', 'DELETE' );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals(
			[
				'message' => 'No notification found with the given ID.',
				'id'      => 'this_id_never_existed',
			],
			$response->get_data()
		);
	}

	public function test_get_notifications_without_permission() {
		wp_set_current_user( 0 );

		$this->service->expects( $this->never() )
			->method( 'get_notifications' );

		$response = $this->do_request( self::ROUTE );

		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_delete_notification_without_permission() {
		wp_set_current_user( 0 );

		$this->service->expects( $this->never() )
			->method( 'dismiss' );

		$this->service->expects( $this->never() )
			->method( 'get_notifications' );

		$response = $this->do_request( self::ROUTE_DELETE, 'DELETE' );

		$this->assertEquals( 401, $response->get_status() );
	}
}
