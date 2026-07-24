<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetImageProxyController
 *
 * Proxies AI-generated images to bypass adblocker issues by fetching images server-side
 * and streaming them back to the client.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AssetImageProxyController extends BaseController {

	/**
	 * Allowed domains.
	 *
	 * @var array
	 */
	private $allowed_domains = [
		'tpc.googlesyndication.com',
	];

	/**
	 * Allowed image MIME types.
	 *
	 * @var array
	 */
	private $allowed_mime_types = [
		'image/jpeg',
		'image/jpg',
		'image/png',
	];

	/**
	 * Maximum image size in bytes (10MB).
	 *
	 * @var int
	 */
	private $max_image_size = 10485760;

	/**
	 * Register rest routes with WordPress.
	 *
	 * Note: this runs on every REST API request (WP fires `rest_api_init` unconditionally
	 * for route discovery), not just requests to this endpoint, so nothing here should
	 * attach global filters. The `rest_pre_serve_request` filter is instead attached from
	 * within `create_image_response()`, which only runs once this endpoint has actually
	 * been dispatched and produced an image response.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/assets/image-proxy',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_image_proxy_callback(),
					'permission_callback' => $this->get_image_proxy_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Permission callback for the image proxy endpoint.
	 *
	 * Allows access via either:
	 * 1. Direct authentication with manage_woocommerce capability
	 * 2. Valid WordPress REST nonce in query parameter (for img tag requests)
	 *
	 * @return callable
	 */
	protected function get_image_proxy_permission_callback(): callable {
		return function ( $request ) {
			// Check if user has direct permission (for API calls with session auth).
			if ( $this->can_manage() ) {
				return true;
			}

			// Check for valid nonce in query parameter (for img tag requests).
			// get_current_user_id() > 0 ensures anonymous sessions cannot use this path.
			$nonce           = $request->get_param( '_wpnonce' );
			$is_logged_in    = get_current_user_id() > 0;
			$has_valid_nonce = $nonce && $is_logged_in && wp_verify_nonce( $nonce, 'wp_rest' );

			if ( ! $has_valid_nonce ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'Sorry, you are not allowed to do that.', 'google-listings-and-ads' ),
					[ 'status' => 403 ]
				);
			}

			return true;
		};
	}

	/**
	 * Get the callback function for proxying images.
	 *
	 * @return callable
	 */
	protected function get_image_proxy_callback(): callable {
		return function ( Request $request ) {
			try {
				$image_url = $request->get_param( 'url' );

				// Check for required parameter.
				if ( empty( $image_url ) ) {
					return new Response(
						[
							'message' => __( 'Image URL is required.', 'google-listings-and-ads' ),
						],
						400
					);
				}

				// Validate URL format.
				if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
					return new Response(
						[
							'message' => __( 'Invalid image URL provided.', 'google-listings-and-ads' ),
						],
						400
					);
				}

				// Check if the domain is allowed.
				$domain = wp_parse_url( $image_url, PHP_URL_HOST );
				if ( ! in_array( $domain, $this->allowed_domains, true ) ) {
					return new Response(
						[
							'code'    => 'domain_not_allowed',
							'message' => __( 'Domain not allowed for image proxying.', 'google-listings-and-ads' ),
						],
						403
					);
				}

				// Fetch the image.
				$response = wp_remote_get(
					$image_url,
					[
						'timeout'     => 30,
						'redirection' => 5,
						'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
						'sslverify'   => true,
					]
				);

				// Check for errors.
				if ( is_wp_error( $response ) ) {
					return new Response(
						[
							'code'    => 'fetch_failed',
							'message' => sprintf(
								/* translators: %s: error message */
								__( 'Failed to fetch image: %s', 'google-listings-and-ads' ),
								$response->get_error_message()
							),
						],
						502
					);
				}

				// Get response code.
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( 200 !== $response_code ) {
					return new Response(
						[
							'code'    => 'invalid_response_code',
							'message' => sprintf(
								/* translators: %d: HTTP status code */
								__( 'Image request returned status code: %d', 'google-listings-and-ads' ),
								$response_code
							),
						],
						$response_code
					);
				}

				// Get content type.
				$content_type = wp_remote_retrieve_header( $response, 'content-type' );

				// Validate content type.
				if ( ! $this->is_valid_image_type( $content_type ) ) {
					return new Response(
						[
							'code'    => 'invalid_content_type',
							'message' => sprintf(
								/* translators: %s: content type */
								__( 'Invalid content type: %s. Expected an image.', 'google-listings-and-ads' ),
								$content_type
							),
						],
						400
					);
				}

				// Get image body.
				$image_data = wp_remote_retrieve_body( $response );

				// Check image size.
				if ( strlen( $image_data ) > $this->max_image_size ) {
					return new Response(
						[
							'code'    => 'image_too_large',
							'message' => sprintf(
								/* translators: %d: maximum size in MB */
								__( 'Image exceeds maximum size of %d MB.', 'google-listings-and-ads' ),
								$this->max_image_size / 1048576
							),
						],
						413
					);
				}

				// Return the image with appropriate headers.
				return $this->create_image_response( $image_data, $content_type );

			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Check if the content type is a valid image type.
	 *
	 * @param string $content_type The content type to check.
	 *
	 * @return bool
	 */
	private function is_valid_image_type( string $content_type ): bool {
		// Remove charset and other parameters from content type.
		$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
		return in_array( $content_type, $this->allowed_mime_types, true );
	}

	/**
	 * Create a response with image data and proper headers.
	 *
	 * Returns a WP_REST_Response that is served as raw binary via rest_pre_serve_request.
	 *
	 * The filter is attached here, rather than in register_routes(), so it's only ever
	 * added when this endpoint has actually produced an image to serve — not on every
	 * REST API request site-wide. WP_REST_Server::serve_request() dispatches the matched
	 * route's callback (which reaches this method) before applying rest_pre_serve_request,
	 * so attaching it here still reaches WP in time to affect this response.
	 *
	 * @param string $image_data   The image binary data.
	 * @param string $content_type The content type of the image.
	 *
	 * @return Response
	 */
	private function create_image_response( string $image_data, string $content_type ): Response {
		add_filter( 'rest_pre_serve_request', [ $this, 'serve_image_response' ], 10, 4 );

		$response = new Response( $image_data, 200 );
		$response->header( 'Content-Type', $content_type );
		$response->header( 'Content-Length', (string) strlen( $image_data ) );
		$response->header( 'Cache-Control', 'public, max-age=86400' );
		$response->header( 'X-Content-Type-Options', 'nosniff' );
		$response->header( 'X-GLA-Image-Proxy', '1' );

		return $response;
	}

	/**
	 * Determine whether a rest_pre_serve_request $result is this endpoint's own successful
	 * image response, i.e. one built by create_image_response(), and should be served as
	 * raw binary rather than left to WordPress's default JSON handling.
	 *
	 * @param mixed $result The value passed to serve_image_response() by WordPress.
	 *
	 * @return bool
	 */
	private function is_image_proxy_response( $result ): bool {
		if ( ! $result instanceof Response ) {
			return false;
		}

		$headers = $result->get_headers();

		return 200 === $result->get_status() && isset( $headers['X-GLA-Image-Proxy'] );
	}

	/**
	 * Serve image proxy response as raw binary, bypassing JSON encoding.
	 *
	 * Hooked onto the `rest_pre_serve_request` filter from create_image_response(), so it's
	 * only attached during requests that actually reach this endpoint's success path — not
	 * on every REST request site-wide. That filter is still global, though: other unrelated
	 * callbacks hooked onto it can pass values that aren't strictly booleans (e.g. null)
	 * before this one runs, so $served and $result must stay untyped/nullable here rather
	 * than throwing under strict_types.
	 *
	 * @param mixed            $served   Whether the request has already been served.
	 * @param mixed            $result   The response to serve.
	 * @param \WP_REST_Request $_request Unused; required by the rest_pre_serve_request signature.
	 * @param \WP_REST_Server  $_server  Unused; required by the rest_pre_serve_request signature.
	 *
	 * @return bool True if served, false to allow default handling.
	 */
	public function serve_image_response( $served, $result, $_request, $_server ): bool {
		if ( $served ) {
			return true;
		}

		if ( ! $this->is_image_proxy_response( $result ) ) {
			return false;
		}

		// Clear any previous output.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		status_header( $result->get_status() );

		foreach ( $result->get_headers() as $key => $value ) {
			header( sprintf( '%s: %s', $key, is_array( $value ) ? implode( ', ', $value ) : $value ) );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $result->get_data();

		// This response has been served; no need to keep evaluating it on this filter for the rest of the request.
		remove_filter( 'rest_pre_serve_request', [ $this, 'serve_image_response' ], 10 );

		return true;
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'url'      => [
				'description'       => __( 'The URL of the image to proxy.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'uri',
				'required'          => true,
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function ( $param ) {
					return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
				},
			],
			'_wpnonce' => [
				'description' => __( 'WordPress nonce for authentication', 'google-listings-and-ads' ),
				'type'        => 'string',
				'required'    => false,
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'asset_image_proxy';
	}
}
