<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\BusinessProfile
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT    = '/wc/gla/business-profile/connect';
	protected const ROUTE_CONNECTION = '/wc/gla/business-profile/connection';

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->controller = new AccountController( $this->server, $this->connection );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url   = 'https://domain.test?auth=1';
		$return_url = add_query_arg(
			[
				'google-service'     => 'business-profile',
				'focus-account-card' => 'business-profile',
			],
			admin_url( 'admin.php?page=wc-admin&path=/google/settings&section=accounts' )
		);

		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->with( $return_url, '' )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals( [ 'url' => $auth_url ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connect_passes_through_login_hint() {
		$auth_url = 'https://domain.test?auth=1';

		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->with( $this->anything(), 'merchant@example.com' )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET', [ 'login_hint' => 'merchant@example.com' ] );

		$this->assertEquals( [ 'url' => $auth_url ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connect_rejects_invalid_login_hint() {
		$this->connection->expects( $this->never() )->method( 'connect' );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET', [ 'login_hint' => 'not-an-email' ] );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_connect_rejects_array_login_hint() {
		$this->connection->expects( $this->never() )->method( 'connect' );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET', [ 'login_hint' => [ 'merchant@example.com' ] ] );

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

	public function test_get_connection_status_when_connected() {
		$status = [
			'status'    => Connection::STATUS_CONNECTED,
			'id'        => '5678',
			'accountId' => '1234',
			'title'     => 'Example Store',
			'address'   => '123 Market St, San Francisco, CA, 94103',
			'placeId'   => 'ChIJexample',
			'mapsUri'   => 'https://maps.google.com/?cid=1',
		];

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( $status );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( $status, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_connection_status_when_disconnected() {
		$this->connection->method( 'get_status' )->willReturn( [ 'status' => Connection::STATUS_DISCONNECTED ] );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'status' => Connection::STATUS_DISCONNECTED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_connection_status_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_connect_route_schema_describes_the_oauth_url() {
		$schema = $this->get_route_schema( self::ROUTE_CONNECT );

		$this->assertSame( 'business_profile_connect', $schema['title'] );
		$this->assertSame( [ 'url' ], array_keys( $schema['properties'] ) );
	}

	public function test_connection_route_schema_describes_the_status() {
		$schema = $this->get_route_schema( self::ROUTE_CONNECTION );

		$this->assertSame( 'business_profile_account', $schema['title'] );
		$this->assertSame(
			[ 'status', 'id', 'accountId', 'title', 'address', 'placeId', 'mapsUri' ],
			array_keys( $schema['properties'] )
		);
	}

	/**
	 * Get the schema a route advertises.
	 *
	 * @param string $route Route path.
	 *
	 * @return array
	 */
	protected function get_route_schema( string $route ): array {
		global $wp_rest_server;

		// get_routes() builds the route options that get_route_options() reads.
		$wp_rest_server->get_routes();

		return call_user_func( $wp_rest_server->get_route_options( $route )['schema'] );
	}

	public function test_disconnect() {
		$this->connection->expects( $this->once() )
			->method( 'disconnect' )
			->willReturn( 'Successfully disconnected.' );

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
}
