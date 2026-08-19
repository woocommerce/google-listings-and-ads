<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SearchConsole\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\SearchConsole
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT    = '/wc/gla/search-console/connect';
	protected const ROUTE_CONNECTION = '/wc/gla/search-console/connection';
	protected const ROUTE_PROPERTY   = '/wc/gla/search-console/property';
	protected const ROUTE_VERIFY     = '/wc/gla/search-console/verify';

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

		$this->connection->expects( $this->once() )
			->method( 'should_skip_auth' )
			->willReturn( false );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals(
			[
				'url'       => $auth_url,
				'skip_auth' => false,
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

	public function test_connection_connected() {
		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn( [ 'status' => Connection::STATE_CONNECTED ] );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'status' => Connection::STATE_CONNECTED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connection_disconnected() {
		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn( [ 'status' => Connection::STATE_DISCONNECTED ] );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'status' => Connection::STATE_DISCONNECTED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * @dataProvider provide_connection_states
	 *
	 * @param string $state The connection state returned by the resolver.
	 */
	public function test_connection_exposes_state( string $state ) {
		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn( [ 'status' => $state ] );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'status' => $state ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function provide_connection_states(): array {
		return [
			'incomplete'        => [ Connection::STATE_INCOMPLETE ],
			'action needed'     => [ Connection::STATE_ACTION_NEEDED ],
			'reconnect'         => [ Connection::STATE_RECONNECT ],
			'connection failed' => [ Connection::STATE_CONNECTION_FAILED ],
		];
	}

	public function test_connection_includes_matches_when_present() {
		$matches = [
			[
				'siteUrl'         => 'https://example.com/',
				'permissionLevel' => 'siteOwner',
			],
		];

		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn(
				[
					'status'  => Connection::STATE_ACTION_NEEDED,
					'matches' => $matches,
				]
			);

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			[
				'status'  => Connection::STATE_ACTION_NEEDED,
				'matches' => $matches,
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_connection_omits_matches_when_absent() {
		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn( [ 'status' => Connection::STATE_CONNECTED ] );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertArrayNotHasKey( 'matches', $response->get_data() );
	}

	public function test_connection_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'get_connection_status' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_property_with_site_url() {
		$this->connection->expects( $this->once() )
			->method( 'select_property' )
			->with( 'https://example.com/' )
			->willReturn( [ 'status' => Connection::STATE_CONNECTED ] );

		$response = $this->do_request( self::ROUTE_PROPERTY, 'POST', [ 'site_url' => 'https://example.com/' ] );

		$this->assertEquals( [ 'status' => Connection::STATE_CONNECTED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_select_property_without_site_url_creates_new() {
		$this->connection->expects( $this->once() )
			->method( 'select_property' )
			->with( null )
			->willReturn( [ 'status' => Connection::STATE_ACTION_NEEDED ] );

		$response = $this->do_request( self::ROUTE_PROPERTY, 'POST' );

		$this->assertEquals( [ 'status' => Connection::STATE_ACTION_NEEDED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_select_property_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'select_property' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_PROPERTY, 'POST', [ 'site_url' => 'https://example.com/gone/' ] );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_verify() {
		$this->connection->expects( $this->once() )
			->method( 'verify_property' )
			->willReturn( [ 'status' => Connection::STATE_CONNECTED ] );

		$response = $this->do_request( self::ROUTE_VERIFY, 'POST' );

		$this->assertEquals( [ 'status' => Connection::STATE_CONNECTED ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_verify_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'verify_property' )
			->willThrowException( new Exception( 'No Search Console property has been selected yet.', 400 ) );

		$response = $this->do_request( self::ROUTE_VERIFY, 'POST' );

		$this->assertEquals( [ 'message' => 'No Search Console property has been selected yet.' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}
}
