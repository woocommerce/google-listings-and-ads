<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\TagManager
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|Connection $connection */
	protected $connection;

	/** @var MockObject|TagManagerSiteTag $site_tag */
	protected $site_tag;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT    = '/wc/gla/tag-manager/connect';
	protected const ROUTE_CONNECTION = '/wc/gla/tag-manager/connection';
	protected const ROUTE_ACCOUNTS   = '/wc/gla/tag-manager/accounts';
	protected const ROUTE_CONTAINERS = '/wc/gla/tag-manager/containers';

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->site_tag   = $this->createMock( TagManagerSiteTag::class );
		$this->controller = new AccountController( $this->server, $this->connection, $this->site_tag );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url   = 'https://domain.test?auth=1';
		$return_url = admin_url( 'admin.php?page=wc-admin&path=/google/settings' );

		$this->connection->expects( $this->once() )
			->method( 'connect' )
			->with( $return_url )
			->willReturn( $auth_url );

		$response = $this->do_request( self::ROUTE_CONNECT, 'GET' );

		$this->assertEquals( [ 'url' => $auth_url ], $response->get_data() );
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

	public function test_get_connection_status() {
		$status = [ 'status' => 'connected' ];

		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willReturn( $status );

		$this->site_tag->expects( $this->once() )
			->method( 'has_injection_failed' )
			->willReturn( false );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals(
			array_merge( $status, [ 'injectionFailed' => false ] ),
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_connection_status_reports_injection_failure() {
		$this->connection->method( 'get_status' )->willReturn( [ 'status' => 'connected' ] );

		$this->site_tag->expects( $this->once() )
			->method( 'has_injection_failed' )
			->willReturn( true );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertTrue( $response->get_data()['injectionFailed'] );
	}

	public function test_get_connection_status_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'get_status' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
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

	public function test_get_accounts() {
		$accounts = [
			[
				'id'   => '123',
				'name' => 'Example Store',
			],
		];

		$this->connection->expects( $this->once() )
			->method( 'list_accounts' )
			->willReturn( $accounts );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals( $accounts, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_accounts_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'list_accounts' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_select_account() {
		$this->connection->expects( $this->once() )
			->method( 'select_account' )
			->with( '123' );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', [ 'id' => '123' ] );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully selected Tag Manager account.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_select_account_requires_id() {
		$this->connection->expects( $this->never() )->method( 'select_account' );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', [] );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_account_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'select_account' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', [ 'id' => '123' ] );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_containers() {
		$containers = [
			[
				'id'       => '456',
				'publicId' => 'GTM-ABCDEFG',
				'name'     => 'Example Store - Web',
			],
		];

		$this->connection->expects( $this->once() )
			->method( 'list_containers' )
			->willReturn( $containers );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'GET' );

		$this->assertEquals( $containers, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_containers_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'list_containers' )
			->willThrowException( new Exception( 'No Tag Manager account has been selected yet.', 400 ) );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'GET' );

		$this->assertEquals( [ 'message' => 'No Tag Manager account has been selected yet.' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_container() {
		$this->connection->expects( $this->once() )
			->method( 'select_container' )
			->with( '456' );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'POST', [ 'id' => '456' ] );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Successfully selected Tag Manager container.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_select_container_requires_id() {
		$this->connection->expects( $this->never() )->method( 'select_container' );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'POST', [] );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_container_with_error() {
		$this->connection->expects( $this->once() )
			->method( 'select_container' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'POST', [ 'id' => '456' ] );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}
}
