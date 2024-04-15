<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\AuthController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use Exception;


/**
 * Class AuthControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI
 */
class AuthControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|OAuthService $oauth_service */
	protected $oauth_service;

	/** @var MockObject|AccountService $oauth_service */
	protected $account_service;

	/** @var AuthController $controller */
	protected $controller;

	protected const ROUTE_AUTHORIZE = '/wc/gla/rest-api/authorize';

	public function setUp(): void {
		parent::setUp();

		$this->oauth_service   = $this->createMock( OAuthService::class );
		$this->account_service = $this->createMock( AccountService::class );
		$this->controller      = new AuthController( $this->server, $this->oauth_service, $this->account_service );
		$this->controller->register();
	}

	public function test_authorize() {
		$expected_auth_url  = 'https://public-api.wordpress.com/oauth2/authorize';
		$expected_auth_url .= '?blog=12345';
		$expected_auth_url .= '&client_id=23456';
		$expected_auth_url .= '&redirect_uri=https://example.com';
		$expected_auth_url .= '&response_type=code';
		$expected_auth_url .= '&scope=wc-partner-access';
		$expected_auth_url .= '&state=base64_encoded_string';

		$this->oauth_service->expects( $this->once() )
			->method( 'get_auth_url' )
			->willReturn( $expected_auth_url );

		$response = $this->do_request( self::ROUTE_AUTHORIZE );

		$this->assertEquals(
			[
				'auth_url' => $expected_auth_url,
				'status'   => null,
			],
			$response->get_data()
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_authorize_invalid_parameter() {
		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'GET', [ 'next_page_name' => 'invalid' ] );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_authorize_with_error() {
		$this->oauth_service->expects( $this->once() )
			->method( 'get_auth_url' )
			->willThrowException( new Exception( 'error', 400 ) );

		$response = $this->do_request( self::ROUTE_AUTHORIZE );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_authorize() {
		$this->account_service->expects( $this->once() )
			->method( 'update_wpcom_api_authorization' )
			->willReturn( true );

		$this->account_service->expects( $this->once() )
			->method( 'delete_wpcom_api_auth_nonce' )
			->willReturn( true );

		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'POST', [ 'status' => 'approved' ] );

		$this->assertEquals( [ 'status' => 'approved' ], $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_update_authorize_missing_params() {
		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'POST' );

		$this->assertEquals( 'Missing parameter(s): status', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_update_authorize_wrong_params() {
		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'POST', [ 'status' => 'wrong-param' ] );

		$this->assertEquals( 'Invalid parameter(s): status', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
