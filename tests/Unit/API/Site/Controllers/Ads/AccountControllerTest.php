<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property RESTServer                $rest_server
 * @property AccountService|MockObject $account
 */
class AccountControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_ACCOUNTS           = '/wc/gla/ads/accounts';
	protected const ROUTE_CONNECTION         = '/wc/gla/ads/connection';
	protected const ROUTE_BILLING_STATUS     = '/wc/gla/ads/billing-status';
	protected const TEST_ACCOUNT_ID          = 1234567890;
	protected const TEST_BILLING_URL         = 'https://domain.test/billing/setup/';
	protected const TEST_BILLING_STATUS      = 'pending';
	protected const TEST_ACCOUNTS            = [
		[
			'id'   => self::TEST_ACCOUNT_ID,
			'name' => 'Ads Account',
		],
		[
			'id'   => 2345678901,
			'name' => 'Other Account',
		],
	];
	protected const TEST_NO_ACCOUNTS         = [];
	protected const TEST_ACCOUNT_CREATE_DATA = [
		'id'          => SELF::TEST_ACCOUNT_ID,
		'billing_url' => SELF::TEST_BILLING_URL,
	];
	protected const TEST_ACCOUNT_LINK_ARGS   = [ 'id' => self::TEST_ACCOUNT_ID ];
	protected const TEST_ACCOUNT_LINK_DATA   = [
		'id'          => SELF::TEST_ACCOUNT_ID,
		'billing_url' => null,
	];
	protected const TEST_CONNECTED_DATA      = [
		'id'       => SELF::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'connected',
	];
	protected const TEST_DISCONNECTED_DATA   = [
		'id'       => 0,
		'currency' => null,
		'symbol'   => '€',
		'status'   => 'disconnected',
	];
	protected const TEST_BILLING_STATUS_DATA = [
		'status'      => self::TEST_BILLING_STATUS,
		'billing_url' => self::TEST_BILLING_URL,
	];

	public function setUp(): void {
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

	public function test_get_accounts_with_no_ids() {
		$this->account->expects( $this->once() )
			->method( 'get_accounts' )
			->willReturn( self::TEST_NO_ACCOUNTS );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );

		$this->assertEquals( self::TEST_NO_ACCOUNTS, $response->get_data() );
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
			->willReturn( self::TEST_ACCOUNT_CREATE_DATA );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( self::TEST_ACCOUNT_CREATE_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_create_account_with_api_exception_data() {
		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willThrowException(
				new ExceptionWithResponseData(
					'error',
					428,
					null,
					[
						'billing_url'    => SELF::TEST_BILLING_URL,
						'billing_status' => SELF::TEST_BILLING_STATUS,
					]
				)
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( SELF::TEST_BILLING_URL, $response->get_data()['billing_url'] );
		$this->assertEquals( SELF::TEST_BILLING_STATUS, $response->get_data()['billing_status'] );
		$this->assertEquals( 428, $response->get_status() );
	}

	public function test_create_account_with_api_exception() {
		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willThrowException( new Exception( 'error' ) );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_link_account() {
		$this->account->expects( $this->once() )
			->method( 'use_existing_account' )
			->with( self::TEST_ACCOUNT_ID);

		$this->account->expects( $this->once() )
			->method( 'setup_account' )
			->willReturn( self::TEST_ACCOUNT_LINK_DATA );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST', self::TEST_ACCOUNT_LINK_ARGS );

		$this->assertEquals( self::TEST_ACCOUNT_LINK_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_connected_account() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_account' )
			->willReturn( self::TEST_CONNECTED_DATA );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( self::TEST_CONNECTED_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_disconnected_account() {
		$this->account->expects( $this->once() )
			->method( 'get_connected_account' )
			->willReturn( self::TEST_DISCONNECTED_DATA );

		$response = $this->do_request( self::ROUTE_CONNECTION, 'GET' );

		$this->assertEquals( self::TEST_DISCONNECTED_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_disconnect_account() {
		$this->account->expects( $this->once() )
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

	public function test_get_billing_status() {
		$this->account->expects( $this->once() )
			->method( 'get_billing_status' )
			->willReturn( self::TEST_BILLING_STATUS_DATA );

		$response = $this->do_request( self::ROUTE_BILLING_STATUS, 'GET' );

		$this->assertEquals( self::TEST_BILLING_STATUS_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

}
