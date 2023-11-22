<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetSuggestionsController
 *
 * @since 2.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AssetSuggestionsController extends BaseController {

	/**
	 * Service used to populate ads suggestions data.
	 *
	 * @var AssetSuggestionsService
	 */
	protected $asset_suggestions_service;

	/**
	 * AssetSuggestionsController constructor.
	 *
	 * @param RESTServer              $server
	 * @param AssetSuggestionsService $asset_suggestions
	 */
	public function __construct( RESTServer $server, AssetSuggestionsService $asset_suggestions ) {
		parent::__construct( $server );
		$this->asset_suggestions_service = $asset_suggestions;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'assets/suggestions',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_assets_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_assets_suggestions_params(),
				],
			]
		);
		$this->register_route(
			'assets/final-url/suggestions',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_final_url_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'search'   => [
				'description'       => __( 'Search for post title or term name', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page' => [
				'description'       => __( 'The number of items to be return', 'google-listings-and-ads' ),
				'type'              => 'number',
				'default'           => 30,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'validate_callback' => 'rest_validate_request_arg',
			],
			'order_by' => [
				'description'       => __( 'Sort retrieved items by parameter', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => 'title',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => [ 'type', 'title', 'url' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the assets suggestions params.
	 *
	 * @return array
	 */
	public function get_assets_suggestions_params(): array {
		return [
			'id'   => [
				'description'       => __( 'Post ID or Term ID.', 'google-listings-and-ads' ),
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'type' => [
				'description'       => __( 'Type linked to the id.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => [ 'post', 'term', 'homepage' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
		];
	}

	/**
	 * Get the callback function for the assets suggestions request.
	 *
	 * @return callable
	 */
	protected function get_assets_suggestions_callback(): callable {
		return function ( Request $request ) {
			try {
				$id   = $request->get_param( 'id' );
				$type = $request->get_param( 'type' );
				return $this->asset_suggestions_service->get_assets_suggestions( $id, $type );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the list of final-url suggestions request.
	 *
	 * @return callable
	 */
	protected function get_final_url_suggestions_callback(): callable {
		return function ( Request $request ) {
			$search   = $request->get_param( 'search' );
			$per_page = $request->get_param( 'per_page' );
			$order_by = $request->get_param( 'order_by' );
			return array_map(
				function ( $item ) use ( $request ) {
					$data = $this->prepare_item_for_response( $item, $request );
					return $this->prepare_response_for_collection( $data );
				},
				$this->asset_suggestions_service->get_final_url_suggestions( $search, $per_page, $order_by )
			);
		};
	}



	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'    => [
				'type'        => 'number',
				'description' => __( 'Post ID or Term ID', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'type'  => [
				'type'        => 'string',
				'description' => __( 'Post, term or homepage', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'enum'        => [ 'post', 'term', 'homepage' ],
				'readonly'    => true,
			],
			'title' => [
				'type'        => 'string',
				'description' => __( 'The post or term title', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'url'   => [
				'type'        => 'string',
				'description' => __( 'The URL linked to the post/term', 'google-listings-and-ads' ),
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
		return 'asset_final_url_suggestions';
	}
}
