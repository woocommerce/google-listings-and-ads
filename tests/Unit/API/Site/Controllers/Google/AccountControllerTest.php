<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;


/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Google
 *
 * @property Connection|MockObject $connection
 * @property AccountController     $controller
 */
class AccountControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_CONNECT     = '/wc/gla/google/connect';
	protected const ROUTE_CONNECTED   = '/wc/gla/google/connected';
	protected const ROUTE_RECONNECTED = '/wc/gla/google/reconnected';
	protected const TEST_EMAIL        = 'john@doe.email';
	protected const TEST_SCOPE        = [ 'https://www.googleapis.com/auth/content' ];

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->controller = new AccountController( $this->server, $this->connection );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url          = 'https://domain.test?auth=1';
		$expected_auth_url = $auth_url . '&from=google-listings-and-ads';

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

	public function test_connect_invalid_parameter() {
		$response = $this->do_request( self::ROUTE_CONNECT, 'GET', [ 'next_page_name' => 'invalid' ] );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 400, $response->get_status() );
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

		$response = $this->do_request( self::ROUTE_CONNECT, 'DELETE' );

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
		$status = [
			'status' => 'connected',
			'email'  => self::TEST_EMAIL,
			'scope'  => self::TEST_SCOPE,
		];

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( $status );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active' => 'yes',
				'email'  => self::TEST_EMAIL,
				'scope'  => self::TEST_SCOPE,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnected() {

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active' => 'no',
				'email'  => '',
				'scope'  => [],
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_reconnected() {
		$status = [
			'status'           => 'connected',
			'email'            => self::TEST_EMAIL,
			'scope'            => self::TEST_SCOPE,
			'merchant_account' => 12345678,
			'merchant_access'  => 'yes',
			'ads_account'      => 23456789,
			'ads_access'       => 'yes',
		];

		$this->connection->expects( $this->once() )
			->method( 'get_reconnect_status' )
			->willReturn( $status );

		$response = $this->do_request( self::ROUTE_RECONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active'           => 'yes',
				'email'            => self::TEST_EMAIL,
				'scope'            => self::TEST_SCOPE,
				'merchant_account' => 12345678,
				'merchant_access'  => 'yes',
				'ads_account'      => 23456789,
				'ads_access'       => 'yes',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_reconnected_with_disconnected_status() {
		$response = $this->do_request( self::ROUTE_RECONNECTED, 'GET' );

		$this->assertEquals(
			[
				'active' => 'no',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test a Jetpack disconnected error since it's a dependency for a connected Google account.
	 */
	public function test_connected_with_jetpack_disconnected() {
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willThrowException( AccountReconnect::jetpack_disconnected() );

		$response = $this->do_request( self::ROUTE_CONNECTED, 'GET' );
		$this->assertEquals( 'JETPACK_DISCONNECTED', $response->get_data()['code'] );
		$this->assertEquals( 401, $response->get_data()['status'] );
		$this->assertEquals( 401, $response->get_status() );
	}
}
