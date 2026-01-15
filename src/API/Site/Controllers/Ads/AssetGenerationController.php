<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ResponseFromExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetGenerationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AssetGenerationController extends BaseController {

	use ResponseFromExceptionTrait;

	/**
	 * Service used to generate assets.
	 *
	 * @var AdsAssetGenerationService
	 */
	protected $service;

	/**
	 * AssetGenerationController constructor.
	 *
	 * @param RESTServer                $server  The REST server instance.
	 * @param AdsAssetGenerationService $service The asset generation service.
	 */
	public function __construct( RESTServer $server, AdsAssetGenerationService $service ) {
		parent::__construct( $server );
		$this->service = $service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/assets/generate-text',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_generate_text_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_generate_text_params(),
				],
			]
		);

		$this->register_route(
			'ads/assets/generate-images',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_generate_images_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_generate_images_params(),
				],
			]
		);
	}

	/**
	 * Get the parameters for the generate-text endpoint.
	 *
	 * @return array
	 */
	protected function get_generate_text_params(): array {
		return [
			'final_url' => [
				'description'       => __( 'The final URL for asset generation', 'google-listings-and-ads' ),
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'types'     => [
				'description'       => __( 'Asset types to generate', 'google-listings-and-ads' ),
				'type'              => 'array',
				'items'             => [
					'type' => 'string',
					'enum' => [ 'headline', 'long_headline', 'description' ],
				],
				'sanitize_callback' => function ( $types ) {
					return array_map( 'sanitize_text_field', $types );
				},
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the parameters for the generate-images endpoint.
	 *
	 * @return array
	 */
	protected function get_generate_images_params(): array {
		return [
			'final_url' => [
				'description'       => __( 'The final URL for asset generation', 'google-listings-and-ads' ),
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'types'     => [
				'description'       => __( 'Asset types to generate', 'google-listings-and-ads' ),
				'type'              => 'array',
				'items'             => [
					'type' => 'string',
					'enum' => [ 'marketing_image', 'square_marketing_image', 'portrait_marketing_image' ],
				],
				'sanitize_callback' => function ( $types ) {
					return array_map( 'sanitize_text_field', $types );
				},
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the callback function for the generate-text request.
	 *
	 * @return callable
	 */
	protected function get_generate_text_callback(): callable {
		return function ( Request $request ) {
			try {
				$final_url = $request->get_param( 'final_url' ) ?: $this->get_site_url();
				$types     = $request->get_param( 'types' ) ?: [ 'headline', 'long_headline', 'description' ];

				// Convert lowercase types to uppercase for service.
				$uppercase_types = $this->convert_types_to_uppercase( $types );

				// Call service.
				$items = $this->service->generate_text(
					[
						'final_url'         => $final_url,
						'asset_field_types' => $uppercase_types,
					]
				);

				// Format response with lowercase types.
				return $this->format_response( $final_url, $items );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the generate-images request.
	 *
	 * @return callable
	 */
	protected function get_generate_images_callback(): callable {
		return function ( Request $request ) {
			try {
				$final_url = $request->get_param( 'final_url' ) ?: $this->get_site_url();
				$types     = $request->get_param( 'types' ) ?: [];

				// Convert lowercase types to uppercase for service (if provided).
				$uppercase_types = ! empty( $types ) ? $this->convert_types_to_uppercase( $types ) : [];

				// Call service.
				$args = [ 'final_url' => $final_url ];
				if ( ! empty( $uppercase_types ) ) {
					$args['asset_field_types'] = $uppercase_types;
				}
				$items = $this->service->generate_images( $args );

				// Format response with lowercase types.
				return $this->format_response( $final_url, $items );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Convert types to uppercase.
	 *
	 * @param array $types Array of lowercase type strings.
	 * @return array Array of uppercase type strings.
	 */
	protected function convert_types_to_uppercase( array $types ): array {
		return array_map( 'strtoupper', $types );
	}

	/**
	 * Format the response with final_url and items.
	 *
	 * @param string $final_url    The final URL.
	 * @param array  $service_items Items from the service (with uppercase types).
	 * @return array Formatted response with lowercase types.
	 */
	protected function format_response( string $final_url, array $service_items ): array {
		$items = [];
		foreach ( $service_items as $item ) {
			$item['type'] = strtolower( $item['type'] );
			$items[]      = $item;
		}

		return [
			'final_url' => $final_url,
			'items'     => $items,
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'final_url' => [
				'type'        => 'string',
				'description' => __( 'The final URL used for generation', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'items'     => [
				'type'        => 'array',
				'description' => __( 'Generated assets', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
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
		return 'asset_generation';
	}
}
