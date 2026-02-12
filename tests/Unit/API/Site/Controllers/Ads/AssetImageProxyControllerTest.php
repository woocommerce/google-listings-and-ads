<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetImageProxyController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use WP_Error;

/**
 * Class AssetImageProxyControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class AssetImageProxyControllerTest extends RESTControllerUnitTest {

	/** @var AssetImageProxyController $controller */
	protected $controller;

	protected const ROUTE_IMAGE_PROXY = '/wc/gla/ads/assets/image-proxy';
	protected const VALID_IMAGE_URL   = 'https://tpc.googlesyndication.com/pimgad/5879294827103938154';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new AssetImageProxyController( $this->server );
		$this->controller->register();
	}

	/**
	 * Test successful image fetch.
	 */
	public function test_successful_image_fetch(): void {
		$image_data = base64_decode( '/9j/4AAQSkZJRg==' ); // Minimal valid JPEG header.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $image_data ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return [
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
						'headers'  => [
							'content-type' => 'image/jpeg',
						],
						'body'     => $image_data,
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $image_data, $response->get_data() );
		$headers = $response->get_headers();
		$this->assertEquals( 'image/jpeg', $headers['Content-Type'] );
	}

	/**
	 * Test failed image fetch (connection error).
	 */
	public function test_failed_image_fetch() {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return new WP_Error( 'http_request_failed', 'Connection timeout' );
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'fetch_failed', $response->get_data()['code'] );
		$this->assertStringContainsString( 'Failed to fetch', $response->get_data()['message'] );
		$this->assertStringContainsString( 'Connection timeout', $response->get_data()['message'] );
		$this->assertEquals( 502, $response->get_status() );
	}

	/**
	 * Test wrong content type (non-image).
	 */
	public function test_wrong_content_type() {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return [
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
						'headers'  => [
							'content-type' => 'text/html',
						],
						'body'     => '<html>Not an image</html>',
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'invalid_content_type', $response->get_data()['code'] );
		$this->assertStringContainsString( 'Invalid content type', $response->get_data()['message'] );
		$this->assertStringContainsString( 'text/html', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test missing URL parameter.
	 */
	public function test_missing_url_parameter() {
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET' );

		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertEquals( 'Missing parameter(s): url', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test image too large (exceeds 10MB limit).
	 */
	public function test_image_too_large() {
		$large_image_data = str_repeat( 'x', 11 * 1024 * 1024 ); // 11MB

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $large_image_data ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return [
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
						'headers'  => [
							'content-type' => 'image/jpeg',
						],
						'body'     => $large_image_data,
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'image_too_large', $response->get_data()['code'] );
		$this->assertStringContainsString( 'exceeds maximum size', $response->get_data()['message'] );
		$this->assertEquals( 413, $response->get_status() );
	}

	/**
	 * Test invalid URL parameter format.
	 */
	public function test_invalid_url_parameter() {
		$params   = [ 'url' => 'not-a-valid-url' ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): url', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test non-200 HTTP response codes from remote server.
	 */
	public function test_non_200_response_code() {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return [
						'response' => [
							'code'    => 404,
							'message' => 'Not Found',
						],
						'headers'  => [],
						'body'     => 'Not Found',
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'invalid_response_code', $response->get_data()['code'] );
		$this->assertStringContainsString( 'status code: 404', $response->get_data()['message'] );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test content-type with charset parameter.
	 */
	public function test_content_type_with_charset() {
		$image_data = base64_decode( 'iVBORw0KGg' ); // Minimal PNG header.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $image_data ) {
				if ( $url === self::VALID_IMAGE_URL ) {
					return [
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
						'headers'  => [
							'content-type' => 'image/png; charset=binary',
						],
						'body'     => $image_data,
					];
				}
				return $preempt;
			},
			10,
			3
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $image_data, $response->get_data() );
		$headers = $response->get_headers();
		$this->assertEquals( 'image/png; charset=binary', $headers['Content-Type'] );
	}

	/**
	 * Data provider for invalid image URL rejection tests.
	 *
	 * @return array[]
	 */
	public function data_invalid_image_urls(): array {
		return [
			'malformed string'      => [ 'not-a-valid-url' ],
			'empty string'          => [ '' ],
			'javascript protocol'   => [ 'javascript:alert(1)' ],
			'data uri'              => [ 'data:text/html,<script>alert(1)</script>' ],
			'relative path'         => [ '/path/to/image.jpg' ],
			'relative path with ..' => [ '../etc/passwd' ],
			'no scheme'             => [ 'example.com/image.jpg' ],
			'spaces only'           => [ '   ' ],
		];
	}

	/**
	 * Test that invalid image URLs are rejected by the endpoint.
	 *
	 * @dataProvider data_invalid_image_urls
	 *
	 * @param string $invalid_url The invalid URL to test.
	 */
	public function test_rejects_invalid_image_urls( string $invalid_url ): void {
		$params   = [ 'url' => $invalid_url ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		// Note: var_export is used to ensure values are always clear. e.g. we have '' (set but blank), '    ' (set but only spaces), etc. that would otherwise be difficult to determine.
		$this->assertEquals( 400, $response->get_status(), 'Expected invalid URL to be rejected: ' . var_export( $invalid_url, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertContains( $data['code'], [ 'rest_invalid_param', 'rest_missing_callback_param' ], 'Expected REST validation error code' );
	}

	/**
	 * Test that authenticated users with manage_woocommerce capability can access the endpoint.
	 */
	public function test_authenticated_user_can_access(): void {
		$image_data = base64_decode( '/9j/4AAQSkZJRg==' ); // Minimal valid JPEG header.

		add_filter(
			'pre_http_request',
			function () use ( $image_data ) {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => $image_data,
					'headers'  => [ 'content-type' => 'image/jpeg' ],
				];
			}
		);

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 200, $response->get_status(), 'Authenticated user with manage_woocommerce should access endpoint' );
	}

	/**
	 * Test that requests with valid nonce can access the endpoint.
	 */
	public function test_valid_nonce_allows_access(): void {
		// Remove admin capabilities to test nonce-only authentication.
		wp_set_current_user( 0 );

		$image_data = base64_decode( '/9j/4AAQSkZJRg==' ); // Minimal valid JPEG header.

		add_filter(
			'pre_http_request',
			function () use ( $image_data ) {
				return [
					'response' => [ 'code' => 200 ],
					'body'     => $image_data,
					'headers'  => [ 'content-type' => 'image/jpeg' ],
				];
			}
		);

		// Create a valid nonce.
		$nonce = wp_create_nonce( 'wp_rest' );

		$params   = [
			'url'      => self::VALID_IMAGE_URL,
			'_wpnonce' => $nonce,
		];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 200, $response->get_status(), 'Valid nonce should grant access to endpoint' );
	}

	/**
	 * Test that unauthenticated requests without nonce are rejected.
	 */
	public function test_unauthenticated_request_rejected(): void {
		// Remove admin capabilities.
		wp_set_current_user( 0 );

		$params   = [ 'url' => self::VALID_IMAGE_URL ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 403, $response->get_status(), 'Unauthenticated request without nonce should be rejected' );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_forbidden', $data['code'] );
	}

	/**
	 * Test that requests with invalid nonce are rejected.
	 */
	public function test_invalid_nonce_rejected(): void {
		// Remove admin capabilities.
		wp_set_current_user( 0 );

		$params   = [
			'url'      => self::VALID_IMAGE_URL,
			'_wpnonce' => 'invalid_nonce_value',
		];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 403, $response->get_status(), 'Invalid nonce should be rejected' );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_forbidden', $data['code'] );
	}
}
