<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAssetGenerationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ResponseFromExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_Error;
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
	 * Maximum allowed length for the image generation prompt.
	 *
	 * @var int
	 */
	protected const MAX_PROMPT_LENGTH = 1500;

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
				'schema' => $this->get_api_response_schema_callback(),
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
				'schema' => $this->get_api_response_schema_callback(),
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
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'types'     => [
				'description'       => __( 'Asset types to generate', 'google-listings-and-ads' ),
				'type'              => 'array',
				'default'           => [],
				'items'             => [
					'type' => 'string',
					'enum' => AdsAssetGenerationService::VALID_TEXT_TYPES,
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
			'final_url'        => [
				'description'       => __( 'The final URL for asset generation', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'prompt'           => [
				'description'       => __( 'The prompt to use for freeform or recontext image generation', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					if ( is_string( $value ) && mb_strlen( $value ) > self::MAX_PROMPT_LENGTH ) {
						return new WP_Error(
							'woocommerce_gla_prompt_too_long',
							__( 'Prompt must be 1500 characters or fewer.', 'google-listings-and-ads' ),
							[ 'status' => 400 ]
						);
					}

					return true;
				},
			],
			'source_image_url' => [
				'description'       => __( 'The source image URL to use for recontext image generation', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'types'            => [
				'description'       => __( 'Asset types to generate', 'google-listings-and-ads' ),
				'type'              => 'array',
				'default'           => [],
				'items'             => [
					'type' => 'string',
					'enum' => AdsAssetGenerationService::VALID_IMAGE_TYPES,
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
			set_time_limit( 90 ); // AI text generation can take time.

			try {
				$final_url = $request->get_param( 'final_url' ) ?: $this->get_site_url();
				$types     = $request->get_param( 'types' ) ?: [];

				// Call service with lowercase types.
				$items = $this->service->generate_text(
					[
						'final_url'         => $final_url,
						'asset_field_types' => $types,
					]
				);

				return [
					'final_url' => $final_url,
					'items'     => $items,
				];
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
			set_time_limit( 90 ); // AI image generation can take time.

			try {
				$final_url        = $request->get_param( 'final_url' ) ?: $this->get_site_url();
				$prompt           = $request->get_param( 'prompt' ) ?: '';
				$source_image_url = $request->get_param( 'source_image_url' ) ?: '';
				$types            = $request->get_param( 'types' ) ?: [];

				// Call service with lowercase types.
				$args = [
					'final_url'        => $final_url,
					'prompt'           => $prompt,
					'source_image_url' => $source_image_url,
				];
				if ( ! empty( $types ) ) {
					$args['asset_field_types'] = $types;
				}
				$items = $this->service->generate_images( $args );

				return [
					'final_url' => $final_url,
					'items'     => $items,
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
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
