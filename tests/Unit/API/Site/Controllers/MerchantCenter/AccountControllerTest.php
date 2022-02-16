<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ApiNotReady;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property RESTServer                $rest_server
 * @property AccountService|MockObject $account
 */
class AccountControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_ACCOUNTS        = '/wc/gla/mc/accounts';
	protected const ROUTE_CLAIM_OVERWRITE = '/wc/gla/mc/accounts/claim-overwrite';
	protected const ROUTE_SWITCH_URL      = '/wc/gla/mc/accounts/switch-url';
	protected const ROUTE_CONNECTION      = '/wc/gla/mc/connection';
	protected const ROUTE_SETUP_STATUS    = '/wc/gla/mc/setup';
	protected const TEST_ACCOUNT_ID       = 12345678;
	protected const TEST_ACCOUNTS         = [
		[
			'id'         => self::TEST_ACCOUNT_ID,
			'subaccount' => true,
			'name'       => 'One',
			'domain'     => 'https://account.one',
		],
		[
			'id'         => 23456781,
			'subaccount' => true,
			'name'       => 'Two',
			'domain'     => 'https://account.two',
		],
	];
	protected const TEST_ACCOUNT_DATA     = [ 'id' => SELF::TEST_ACCOUNT_ID ];
	protected const TEST_ACCOUNT_RESPONSE = [
		'id'         => SELF::TEST_ACCOUNT_ID,
		'subaccount' => null,
		'name'       => null,
		'domain'     => null,
	];
	protected const TEST_RETRY_AFTER      = 10;
	protected const TEST_CONNECTED_DATA   = [
		'id'       => SELF::TEST_ACCOUNT_ID,
		'status'   => 'connected',
	];
	protected const TEST_STATUS_DATA      = [
		'status' => 'complete',
	];

	public function setUp() {
		parent::setUp();

		$this->account    = $this->createMock( AccountService::class );
		$this->controller = new AccountController( $this->server, $this->account );
		$this->controller->register();
	}

	public function test_get_accounts() {
		$this->account->expects( $this->once() )
			->method( 'get_accounts' )
			->willReturn( self::TEST_ACCOUNTS );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals( self::TEST_ACCOUNTS, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_accounts_with_api_exception() {
		$this->account->expects( $this->once() )
			->method( 'get_accounts' )
			->willThrowException( new Exception( 'error', 401 ) );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_create_account() {
		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willReturn( self::TEST_ACCOUNT_DATA );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( self::TEST_ACCOUNT_RESPONSE, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_account_with_api_exception_data() {
		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willThrowException(
				new ExceptionWithResponseData(
					'error',
					406,
					null,
					[ 'id' => SELF::TEST_ACCOUNT_ID ]
				)
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( SELF::TEST_ACCOUNT_ID, $response->get_data()['id'] );
		$this->assertEquals( 406, $response->get_status() );
	}

	public function test_create_account_with_time_to_wait() {
		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willThrowException(
				new ApiNotReady(
					'error',
					503,
					null,
					[ 'retry_after' => SELF::TEST_RETRY_AFTER ]
				)
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( SELF::TEST_RETRY_AFTER, $response->get_data()['retry_after'] );
		$this->assertEquals( SELF::TEST_RETRY_AFTER, $response->get_headers()['Retry-After'] );
		$this->assertEquals( 503, $response->get_status() );
	}

	public function test_link_account() {
		$this->account->expects( $this->once() )
			->method( 'use_existing_account_id' )
			->with( self::TEST_ACCOUNT_ID );

		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willReturn( self::TEST_ACCOUNT_DATA );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', self::TEST_ACCOUNT_DATA );

		$this->assertEquals( self::TEST_ACCOUNT_RESPONSE, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_overwrite_claim() {
		$this->account->expects( $this->once() )
			->method( 'overwrite_claim' )
			->willReturn( self::TEST_ACCOUNT_DATA );

		$response = $this->do_request( self::ROUTE_CLAIM_OVERWRITE, 'POST', self::TEST_ACCOUNT_DATA );

		$this->assertEquals( self::TEST_ACCOUNT_RESPONSE, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_switch_url() {
		$this->account->expects( $this->once() )
			->method( 'switch_url' )
			->willReturn( self::TEST_ACCOUNT_DATA );

		$response = $this->do_request( self::ROUTE_SWITCH_URL, 'POST', self::TEST_ACCOUNT_DATA );

		$this->assertEquals( self::TEST_ACCOUNT_RESPONSE, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_connected_account() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_status' )
			->willReturn( self::TEST_CONNECTED_DATA );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( self::TEST_CONNECTED_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_setup_status() {
		$this->account->expects( $this->once() )
			->method( 'get_setup_status' )
			->willReturn( self::TEST_STATUS_DATA );

		$response = $this->do_request( self::ROUTE_SETUP_STATUS, 'GET' );

		$this->assertEquals( self::TEST_STATUS_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnect_account() {
		$this->account->expects( $this->once() )
			->method( 'disconnect' );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'DELETE' );

		$this->assertEquals(
			[
				'status'  => 'success',
				'message' => 'Merchant Center account successfully disconnected.',
			],
			$response->get_data()
		);
		$this->assertEquals( 200, $response->get_status() );
	}

}
