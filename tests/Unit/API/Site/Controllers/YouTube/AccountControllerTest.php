<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\YouTube;

use Automattic\WooCommerce\GoogleListingsAndAds\API\YouTube\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\YouTube\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;


/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\YouTube
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT    = '/wc/gla/youtube/connect';
	protected const ROUTE_CONNECTION = '/wc/gla/youtube/connection';

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->controller = new AccountController( $this->server, $this->connection );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url = 'https://domain.test?auth=1';

		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'url' => $auth_url,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connect_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_disconnect() {
		$this->connection->expects( $this->once() )
			->method( 'disconnect' );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully disconnected.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connected() {
		// Mock status response.
		$status = [
			'status' => 'connected',
		];

		// Mock channels response.
		$channels = [
			'items' => [
				[
					'id'      => 1234,
					'snippet' => [
						'title' => 'Channel 1',
					],
				],
			],
		];

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( $status );

		$this->connection->expects( $this->once() )
			->method( 'get_channels' )
			->willReturn( $channels );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'connected',
				'channel' => [
					'id'    => 1234,
					'label' => 'Channel 1',
				],
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnected() {
		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => 'disconnected',
				'channel' => [],
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}
}
