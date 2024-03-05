<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI\AuthController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;


/**
 * Class AuthControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\RestAPI
 */
class AuthControllerTest extends RESTControllerUnitTest {

	public const DUMMY_BLOG_ID = '123';

	/** @var AuthController $controller */
	protected $controller;

	protected const ROUTE_AUTHORIZE = '/wc/gla/rest-api/authorize';

	public function setUp(): void {
		parent::setUp();

		$this->controller = new AuthController( $this->server );
		$this->controller->register();

		// Mock the Blog ID from Jetpack
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				if ( $name === 'id' ) {
					return self::DUMMY_BLOG_ID;
				}

				return $value;
			},
			10,
			2
		);

		// Mock admin URL.
		add_filter(
			'admin_url',
			function ( $url, $path ) {
				return 'https://admin-example.com/wp-admin/' . $path;
			},
			10,
			2
		);
	}

	public function test_authorize() {
		$blog_id = self::DUMMY_BLOG_ID;

		$client_id    = '91299';
		$redirect_uri = 'https://woo.com';
		$nonce        = 'nonce-123';

		$merchant_redirect_url         = 'https://admin-example.com/wp-admin/admin.php?page=wc-admin&path=/google/setup-mc';
		$merchant_redirect_url_encoded = urlencode_deep( $merchant_redirect_url );
		$state_raw                     = "nonce={$nonce}&redirect_url={$merchant_redirect_url_encoded}";
		$state                         = base64_encode( $state_raw );

		$expected_auth_url  = 'https://public-api.wordpress.com/oauth2/authorize';
		$expected_auth_url .= "?blog={$blog_id}";
		$expected_auth_url .= "&client_id={$client_id}";
		$expected_auth_url .= "&redirect_uri={$redirect_uri}";
		$expected_auth_url .= '&response_type=code';
		$expected_auth_url .= '&scope=wc-partner-access';
		$expected_auth_url .= "&state={$state}";
		$expected_auth_url  = esc_url_raw( $expected_auth_url );

		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'GET' );
		$data     = $response->get_data();
		$auth_url = $data['auth_url'];

		// Compare the auth URLs.
		// Removing the "=" sign from the end of the string because sometimes
		// base64_encode function will add "=" signs as paddings.
		$this->assertEquals(
			rtrim( $expected_auth_url, '=' ),
			rtrim( $auth_url, '=' )
		);

		$this->assertEquals( 200, $response->get_status() );

		// Compare the state query parameters from the auth URL.
		$parsed_url = wp_parse_url( $auth_url );
		parse_str( $parsed_url['query'], $parsed_query );
		$response_state_raw = base64_decode( $parsed_query['state'] );
		$this->assertEquals(
			$state_raw,
			$response_state_raw
		);

		// Ensure the base64 encoded state query has correct value.
		parse_str( $response_state_raw, $parsed_state );
		$this->assertEquals(
			$nonce,
			$parsed_state['nonce']
		);
		$this->assertEquals(
			$merchant_redirect_url,
			$parsed_state['redirect_url']
		);
	}

	public function test_authorize_invalid_parameter() {
		$response = $this->do_request( self::ROUTE_AUTHORIZE, 'GET', [ 'next_page_name' => 'invalid' ] );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 400, $response->get_status() );
	}
}
