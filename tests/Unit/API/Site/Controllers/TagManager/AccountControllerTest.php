<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshTagManagerAdsConversionConflict;
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

	/** @var MockObject|RefreshTagManagerAdsConversionConflict $conflict_job */
	protected $conflict_job;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_CONNECT    = '/wc/gla/tag-manager/connect';
	protected const ROUTE_CONNECTION = '/wc/gla/tag-manager/connection';
	protected const ROUTE_ACCOUNTS   = '/wc/gla/tag-manager/accounts';
	protected const ROUTE_CONTAINERS = '/wc/gla/tag-manager/containers';
	protected const ROUTE_SETTINGS   = '/wc/gla/tag-manager/settings';

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock( Connection::class );
		$this->site_tag     = $this->createMock( TagManagerSiteTag::class );
		$this->conflict_job = $this->createMock( RefreshTagManagerAdsConversionConflict::class );

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )
			->with( RefreshTagManagerAdsConversionConflict::class )
			->willReturn( $this->conflict_job );

		$this->controller = new AccountController( $this->server, $this->connection, $this->site_tag, $job_repository );
		$this->controller->register();
	}

	public function test_connect() {
		$auth_url   = 'https://domain.test?auth=1';
		$return_url = add_query_arg(
			'google-service',
			'tag-manager',
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

	public function test_disconnect() {
		$this->connection->expects( $this->once() )
			->method( 'disconnect' )
			->willReturn( 'Successfully disconnected.' );
		$this->conflict_job->expects( $this->once() )->method( 'unschedule' );

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

	public function test_get_settings_reads_stored_data_only() {
		$this->connection->method( 'is_snippet_injection_enabled' )->willReturn( false );
		$this->connection->expects( $this->never() )->method( 'refresh_ads_conversion_conflict' );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'GET' );

		$this->assertEquals( [ 'snippetInjectionEnabled' => false ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_update_settings_disables_snippet_injection() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_id' => '456' ] );
		$this->connection->expects( $this->once() )
			->method( 'set_snippet_injection_enabled' )
			->with( false )
			->willReturn( true );
		$this->connection->method( 'is_snippet_injection_enabled' )->willReturn( false );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', [ 'snippet_injection_enabled' => false ] );

		$this->assertEquals( [ 'snippetInjectionEnabled' => false ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_update_settings_returns_stored_value_when_unchanged() {
		$this->connection->method( 'get_connection_data' )->willReturn( [ 'container_id' => '456' ] );
		$this->connection->method( 'set_snippet_injection_enabled' )->willReturn( false );
		$this->connection->method( 'is_snippet_injection_enabled' )->willReturn( true );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', [ 'snippet_injection_enabled' => true ] );

		$this->assertEquals( [ 'snippetInjectionEnabled' => true ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_update_settings_rejects_when_no_container_connected() {
		$this->connection->method( 'get_connection_data' )->willReturn( [] );
		$this->connection->expects( $this->never() )->method( 'set_snippet_injection_enabled' );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', [ 'snippet_injection_enabled' => false ] );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_settings_requires_snippet_injection_enabled() {
		$this->connection->expects( $this->never() )->method( 'set_snippet_injection_enabled' );

		$response = $this->do_request( self::ROUTE_SETTINGS, 'POST', [] );

		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertEquals( 400, $response->get_status() );
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

	public function test_get_accounts_with_tag_manager_api_error() {
		$this->connection->expects( $this->once() )
			->method( 'list_accounts' )
			->willThrowException(
				new TagManagerApiException( 403, [ 'message' => 'Not authorized' ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals(
			[
				'code'    => 'API_ERROR',
				'message' => 'Not authorized',
				'data'    => [ 'message' => 'Not authorized' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_select_account() {
		$this->connection->expects( $this->once() )
			->method( 'select_account' )
			->with( '123' );
		$this->conflict_job->expects( $this->once() )->method( 'unschedule' );

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

	public function test_select_account_with_tag_manager_api_error() {
		$this->connection->expects( $this->once() )
			->method( 'select_account' )
			->willThrowException(
				new TagManagerApiException( 403, [ 'message' => 'Not authorized' ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', [ 'id' => '123' ] );

		$this->assertEquals(
			[
				'code'    => 'API_ERROR',
				'message' => 'Not authorized',
				'data'    => [ 'message' => 'Not authorized' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 403, $response->get_status() );
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

	public function test_get_containers_with_tag_manager_api_error() {
		$this->connection->expects( $this->once() )
			->method( 'list_containers' )
			->willThrowException(
				new TagManagerApiException( 403, [ 'message' => 'Not authorized' ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'GET' );

		$this->assertEquals(
			[
				'code'    => 'API_ERROR',
				'message' => 'Not authorized',
				'data'    => [ 'message' => 'Not authorized' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_select_container() {
		$this->connection->expects( $this->once() )
			->method( 'select_container' )
			->with( '456' );
		$this->conflict_job->expects( $this->once() )->method( 'schedule' );

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
		$this->conflict_job->expects( $this->never() )->method( 'schedule' );

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'POST', [ 'id' => '456' ] );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_select_container_with_tag_manager_api_error() {
		$this->connection->expects( $this->once() )
			->method( 'select_container' )
			->willThrowException(
				new TagManagerApiException( 403, [ 'message' => 'Not authorized' ], __METHOD__ )
			);

		$response = $this->do_request( self::ROUTE_CONTAINERS, 'POST', [ 'id' => '456' ] );

		$this->assertEquals(
			[
				'code'    => 'API_ERROR',
				'message' => 'Not authorized',
				'data'    => [ 'message' => 'Not authorized' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 403, $response->get_status() );
	}
}
