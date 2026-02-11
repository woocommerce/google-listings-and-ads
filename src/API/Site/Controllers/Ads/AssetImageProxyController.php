<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
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
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\GenAI
 */
class AssetImageProxyController extends BaseController {

	/**
	 * Allowed image MIME types.
	 *
	 * @var array
	 */
	protected $allowed_mime_types = [
		'image/jpeg',
		'image/jpg',
		'image/png',
	];

	/**
	 * Maximum image size in bytes (10MB).
	 *
	 * @var int
	 */
	protected $max_image_size = 10485760;

	/**
	 * AssetImageProxyController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
	}

	/**
	 * Register rest routes with WordPress.
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

		add_filter( 'rest_pre_serve_request', [ $this, 'serve_image_response' ], 10, 4 );
	}

	/**
	 * Permission callback for the image proxy endpoint.
	 *
	 * @return callable
	 */
	protected function get_image_proxy_permission_callback(): callable {
		return '__return_true';
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
	protected function is_valid_image_type( string $content_type ): bool {
		// Remove charset and other parameters from content type.
		$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
		return in_array( $content_type, $this->allowed_mime_types, true );
	}

	/**
	 * Create a response with image data and proper headers.
	 *
	 * Returns a WP_REST_Response that is served as raw binary via rest_pre_serve_request.
	 *
	 * @param string $image_data   The image binary data.
	 * @param string $content_type The content type of the image.
	 *
	 * @return Response
	 */
	protected function create_image_response( string $image_data, string $content_type ): Response {
		$response = new Response( $image_data, 200 );
		$response->header( 'Content-Type', $content_type );
		$response->header( 'Content-Length', (string) strlen( $image_data ) );
		$response->header( 'Cache-Control', 'public, max-age=31536000, immutable' );
		$response->header( 'X-Content-Type-Options', 'nosniff' );
		$response->header( 'X-GLA-Image-Proxy', '1' );

		return $response;
	}

	/**
	 * Serve image proxy response as raw binary, bypassing JSON encoding.
	 *
	 * @param bool             $served  Whether the request has already been served.
	 * @param Response         $result  The response to serve.
	 * @param \WP_REST_Request $request The request instance.
	 * @param \WP_REST_Server  $server  The server instance.
	 *
	 * @return bool True if served, false to allow default handling.
	 */
	public function serve_image_response( bool $served, Response $result, $request, $server ): bool {
		if ( $served ) {
			return true;
		}

		$headers = $result->get_headers();
		if ( $result->get_status() !== 200 || ! isset( $headers['X-GLA-Image-Proxy'] ) ) {
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

		return true;
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'url' => [
				'description'       => __( 'The URL of the image to proxy.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'format'            => 'uri',
				'required'          => true,
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function ( $param ) {
					return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
				},
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
