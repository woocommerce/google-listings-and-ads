<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests related to the asset groups.
 * See https://developers.google.com/google-ads/api/reference/rpc/v11/AssetGroup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AssetGroupController extends BaseController {

	/**
	 * The AdsAssetGroup class.
	 *
	 * @var AdsAssetGroup $ads_asset_group
	 */
	protected $ads_asset_group;

	/**
	 * AssetGroupController constructor.
	 *
	 * @param RESTServer    $rest_server
	 * @param AdsAssetGroup $ads_asset_group
	 */
	public function __construct( RESTServer $rest_server, AdsAssetGroup $ads_asset_group ) {
		parent::__construct( $rest_server );
		$this->ads_asset_group = $ads_asset_group;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/campaigns/(?P<id>[\d]+)/assetgroups',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_asset_groups_by_campaign_id_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_asset_group_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the assets groups params.
	 *
	 * @return array
	 */
	public function get_asset_group_params(): array {
		return [
			'id' => [
				'description'       => __( 'Campaign ID.', 'google-listings-and-ads' ),
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}


	/**
	 * @return callable
	 */
	protected function get_asset_groups_by_campaign_id_callback(): callable {
		return function( Request $request ) {
			try {
				$campaign_id = $request->get_param( 'id' );
				return array_map(
					function( $item ) use ( $request ) {
						$data = $this->prepare_item_for_response( $item, $request );
						return $this->prepare_response_for_collection( $data );
					},
					$this->ads_asset_group->get_asset_groups_by_campaign_id( $campaign_id )
				);

			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}

		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'               => [
				'type'        => 'number',
				'description' => __( 'Asset Group ID', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'final_url'        => [
				'type'        => 'string',
				'description' => __( 'Final URL', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],

			],
			'display_url_path' => [
				'type'        => 'array',
				'description' => __( 'Text that may appear appended to the url displayed in the ad.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'assets'           => [
				'type'        => 'array',
				'description' => __( 'Asset is a part of an ad which can be shared across multiple ads. It can be an image, headlines, descriptions, etc.', 'google-listings-and-ads' ),
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'square_marketing_image'   => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'marketing_image'          => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'logo'                     => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'business_name'            => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'headline'                 => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'description'              => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'long_headline'            => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
						'call_to_action_selection' => [
							'type'     => 'array',
							'items'    => $this->get_schema_asset(),
							'required' => false,
						],
					],
				],
			],

		];
	}

	/**
	 * Get the item schema for the asset.
	 *
	 * @return array
	 */
	protected function get_schema_asset() {
		return [
			'type'       => 'object',
			'properties' => [
				'id'      => [
					'type'        => 'number',
					'description' => __( 'Asset ID', 'google-listings-and-ads' ),
				],
				'content' => [
					'type'        => 'string',
					'description' => __( 'Asset content', 'google-listings-and-ads' ),
				],
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
		return 'asset-group';
	}
}
