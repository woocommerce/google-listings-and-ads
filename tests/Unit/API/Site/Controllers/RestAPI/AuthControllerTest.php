<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\AuthController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
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

	/** @var AuthController $controller */
	protected $controller;

	protected const ROUTE_AUTHORIZE = '/wc/gla/rest-api/authorize';

	public function setUp(): void {
		parent::setUp();

		$this->oauth_service = $this->createMock( OAuthService::class );
		$this->controller    = new AuthController( $this->server, $this->oauth_service );
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
		$expected_auth_url  = $expected_auth_url;

		$this->oauth_service->expects( $this->once() )
			->method( 'get_auth_url' )
			->willReturn( $expected_auth_url );

		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'GET' );
		$data     = $response->get_data();

		$this->assertEquals(
			[
				'auth_url' => $expected_auth_url,
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

		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'GET' );

		$this->assertEquals( [ 'message' => 'error' ], $response->get_data() );
		$this->assertEquals( 400, $response->get_status() );
	}
}
