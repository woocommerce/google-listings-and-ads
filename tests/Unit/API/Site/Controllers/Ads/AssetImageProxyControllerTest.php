<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AssetImageProxyController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

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
	 * Test domain not allowed for image proxying.
	 */
	public function test_domain_not_allowed_for_image_proxying() {
		$params   = [ 'url' => 'https://nonvaliddomain.com/image.jpg' ];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 'domain_not_allowed', $response->get_data()['code'] );
		$this->assertEquals( 403, $response->get_status() );
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
	 *
	 * Uses a subscriber-level user: logged in (user ID > 0) but without manage_woocommerce,
	 * so can_manage() fails and the nonce path is exercised.
	 */
	public function test_valid_nonce_allows_access(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

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

		// Create a valid nonce for the subscriber user.
		$nonce = wp_create_nonce( 'wp_rest' );

		$params   = [
			'url'      => self::VALID_IMAGE_URL,
			'_wpnonce' => $nonce,
		];
		$response = $this->do_request( self::ROUTE_IMAGE_PROXY, 'GET', $params );

		$this->assertEquals( 200, $response->get_status(), 'Logged-in user with valid nonce should access endpoint' );
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

	/**
	 * Data provider of non-bool values for $served, the kind of value another callback
	 * hooked onto the shared `rest_pre_serve_request` filter chain could pass through.
	 *
	 * @return array[]
	 */
	public function data_non_bool_served_values(): array {
		return [
			'null'         => [ null ],
			'zero'         => [ 0 ],
			'empty string' => [ '' ],
			'empty array'  => [ [] ],
		];
	}

	/**
	 * Regression test for GOOWOO-834: `rest_pre_serve_request` is a global filter, so
	 * WordPress core or another plugin's callback earlier in the same chain can pass a
	 * non-bool value for $served (a literal `null` did exactly this in production). A
	 * strict `bool` type hint on that parameter caused an uncaught TypeError for REST
	 * responses that had nothing to do with this endpoint. serve_image_response() must
	 * tolerate this instead of crashing.
	 *
	 * @dataProvider data_non_bool_served_values
	 *
	 * @param mixed $served A non-bool value for the $served argument.
	 */
	public function test_serve_image_response_does_not_throw_on_non_bool_served( $served ): void {
		$unrelated_response = new Response( [ 'some' => 'data' ], 200 );
		$request            = new Request( 'GET', '/wp/v2/posts' );

		global $wp_rest_server;

		$result = $this->controller->serve_image_response( $served, $unrelated_response, $request, $wp_rest_server );

		$this->assertFalse( $result, 'A response without the image-proxy header should not be served as raw binary.' );
	}

	/**
	 * Regression test for GOOWOO-834: $result may not be a WP_REST_Response at all if an
	 * earlier callback on the same filter chain replaced it (e.g. with a WP_Error).
	 * serve_image_response() must not assume the type.
	 */
	public function test_serve_image_response_does_not_throw_on_non_response_result(): void {
		$request = new Request( 'GET', '/wp/v2/posts' );

		global $wp_rest_server;

		$result = $this->controller->serve_image_response( false, new WP_Error( 'some_error' ), $request, $wp_rest_server );

		$this->assertFalse( $result );
	}

	/**
	 * Regression test for GOOWOO-834: serve_image_response() must still correctly serve
	 * the proxied image when $served is a falsy non-bool value — WordPress passed a
	 * literal null in production — rather than mistaking it for "already served" or
	 * failing before reaching the success path.
	 */
	public function test_serve_image_response_serves_image_when_served_is_null(): void {
		$image_data = base64_decode( '/9j/4AAQSkZJRg==' ); // Minimal valid JPEG header.

		$response = new Response( $image_data, 200 );
		$response->header( 'Content-Type', 'image/jpeg' );
		$response->header( 'X-GLA-Image-Proxy', '1' );

		$request = new Request( 'GET', self::ROUTE_IMAGE_PROXY );

		global $wp_rest_server;

		ob_start();
		$served = $this->controller->serve_image_response( null, $response, $request, $wp_rest_server );
		$output = ob_get_clean();

		$this->assertTrue( $served );
		$this->assertEquals( $image_data, $output );
	}
}
