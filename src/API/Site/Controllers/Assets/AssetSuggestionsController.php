<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AssetSuggestionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetSuggestionsController
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Assets
 */
class AssetSuggestionsController extends BaseController {

	/**
	 * Service used to populate ads suggestions data.
	 *
	 * @var AssetSuggestionsService
	 */
	protected $asset_suggestion_service;

	/**
	 * AssetSuggestionsController constructor.
	 *
	 * @param RESTServer              $server
	 * @param AssetSuggestionsService $asset_suggestion
	 */
	public function __construct( RESTServer $server, AssetSuggestionsService $asset_suggestion ) {
		parent::__construct( $server );
		$this->asset_suggestion_service = $asset_suggestion;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'assets/final-url/suggestions',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_final_urls_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the list of final-url suggestions request.
	 *
	 * @return callable
	 */
	protected function get_final_urls_suggestions_callback(): callable {
		return function( Request $request ) {
				return array_map(
					function( $item ) use ( $request ) {
						$data = $this->prepare_item_for_response( $item, $request );
						return $this->prepare_response_for_collection( $data );
					},
					$this->asset_suggestion_service->get_final_urls_suggestions()
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
			'id'        => [
				'type'        => 'number',
				'description' => __( 'Post ID or Term ID', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'type'      => [
				'type'        => 'string',
				'description' => __( 'Post or term', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'enum'        => [ 'post', 'term' ],
				'readonly'    => true,
			],
			'post_type' => [
				'type'        => 'string',
				'description' => __( 'The post type', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'title'     => [
				'type'        => 'string',
				'description' => __( 'The post or term title', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'url'       => [
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
		return 'asset_final_urls_suggestions';
	}

}
