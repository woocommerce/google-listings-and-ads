<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class AccountControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|AccountService $account */
	protected $account;

	/** @var AccountController $controller */
	protected $controller;

	protected const ROUTE_ACCOUNTS            = '/wc/gla/ads/accounts';
	protected const ROUTE_CONNECTION          = '/wc/gla/ads/connection';
	protected const ROUTE_BILLING_STATUS      = '/wc/gla/ads/billing-status';
	protected const ROUTE_ACCEPTED_DATA_TERMS = '/wc/gla/ads/accepted-customer-data-terms';
	protected const ROUTE_UPDATED_EC_STATUS   = '/wc/gla/ads/enhanced-conversion-status';
	protected const ROUTE_GET_EC_STATUS       = '/wc/gla/ads/enhanced-conversion-status';
	protected const TEST_ACCOUNT_ID           = 1234567890;
	protected const TEST_BILLING_URL          = 'https://domain.test/billing/setup/';
	protected const TEST_BILLING_STATUS       = 'pending';
	protected const TEST_ACCOUNTS             = [
		[
			'id'   => self::TEST_ACCOUNT_ID,
			'name' => 'Ads Account',
		],
		[
			'id'   => 2345678901,
			'name' => 'Other Account',
		],
	];
	protected const TEST_NO_ACCOUNTS          = [];
	protected const TEST_ACCOUNT_CREATE_DATA  = [
		'id'          => self::TEST_ACCOUNT_ID,
		'billing_url' => self::TEST_BILLING_URL,
	];
	protected const TEST_ACCOUNT_LINK_ARGS    = [ 'id' => self::TEST_ACCOUNT_ID ];
	protected const TEST_ACCOUNT_LINK_DATA    = [
		'id'          => self::TEST_ACCOUNT_ID,
		'billing_url' => null,
	];
	protected const TEST_CONNECTED_DATA       = [
		'id'       => self::TEST_ACCOUNT_ID,
		'currency' => 'EUR',
		'symbol'   => '€',
		'status'   => 'connected',
	];
	protected const TEST_DISCONNECTED_DATA    = [
		'id'       => 0,
		'currency' => null,
		'symbol'   => '€',
		'status'   => 'disconnected',
	];
	protected const TEST_BILLING_STATUS_DATA  = [
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

	public function test_get_accounts_empty_set() {
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
						'billing_url'    => self::TEST_BILLING_URL,
						'billing_status' => self::TEST_BILLING_STATUS,
					]
				)
			);

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'POST' );

		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( self::TEST_BILLING_URL, $response->get_data()['billing_url'] );
		$this->assertEquals( self::TEST_BILLING_STATUS, $response->get_data()['billing_status'] );
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
			->with( self::TEST_ACCOUNT_ID );

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

	public function test_get_accepted_customer_data_terms() {
		$expected_response = [ 'status' => 'pending' ];
		$this->account->expects( $this->once() )
		->method( 'get_accepted_customer_data_terms' )
		->willReturn( $expected_response );

		$response = $this->do_request( self::ROUTE_ACCEPTED_DATA_TERMS, 'GET' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	public function test_update_enhanced_conversion_status() {
		$expected_response = [ 'status' => 'pending' ];
		$this->account->expects( $this->once() )
		->method( 'update_enhanced_conversion_status' )
		->willReturn( $expected_response );

		$response = $this->do_request( self::ROUTE_UPDATED_EC_STATUS, 'POST', [ 'status' => 'pending' ] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	public function test_update_enhanced_conversion_status_with_invalid_status() {
		$response = $this->do_request( self::ROUTE_UPDATED_EC_STATUS, 'POST', [ 'status' => 'invalid' ] );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_enhanced_conversion_status() {
		$expected_response = [ 'status' => 'pending' ];
		$this->account->expects( $this->once() )
		->method( 'get_enhanced_conversion_status' )
		->willReturn( $expected_response );

		$response = $this->do_request( self::ROUTE_GET_EC_STATUS, 'GET' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	public function test_update_enhanced_conversion_status_only_accepts_lowercase_arg() {
		$response = $this->do_request( self::ROUTE_UPDATED_EC_STATUS, 'POST', [ 'status' => 'PENDING' ] );

		$data = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( $data['code'], 'rest_invalid_param' );
	}

	/**
	 * Test a Google disconnected error since it's a dependency for a connected Ads account.
	 */
	public function test_connected_with_google_disconnected() {
		$this->account->expects( $this->once() )
			->method( 'get_accounts' )
			->willThrowException( AccountReconnect::google_disconnected() );

		$response = $this->do_request( self::ROUTE_ACCOUNTS, 'GET' );
		$this->assertEquals( 'GOOGLE_DISCONNECTED', $response->get_data()['code'] );
		$this->assertEquals( 401, $response->get_data()['status'] );
		$this->assertEquals( 401, $response->get_status() );
	}
}
