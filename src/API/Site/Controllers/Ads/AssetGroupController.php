<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAssetGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
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
			'ads/campaigns/asset-groups/(?P<id>[\d]+)',
			[
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->edit_asset_group_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_edit_params(),
				],
			]
		);
		$this->register_route(
			'ads/campaigns/(?P<id>[\d]+)/asset-groups',
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
	 * Get the schema for the asset group.
	 *
	 * @return array
	 */
	public function get_asset_group_fields(): array {
		return [
			'final_url' => [
				'type'        => 'string',
				'description' => __( 'Final URL.', 'google-listings-and-ads' ),
			],
			'path1'     => [
				'type'        => 'string',
				'description' => __( 'Asset Group path 1.', 'google-listings-and-ads' ),
			],
			'path2'     => [
				'type'        => 'string',
				'description' => __( 'Asset Group path 2.', 'google-listings-and-ads' ),
			],
		];
	}

	/**
	 * Get the edit params to update an asset group.
	 */
	public function get_edit_params() {
		return array_merge(
			[
				'id'     => [
					'description' => __( 'Asset Group ID.', 'google-listings-and-ads' ),
					'type'        => 'integer',
					'required'    => true,
				],
				'assets' => [
					'type'        => 'array',
					'description' => __( 'List of asset to be edited.', 'google-listings-and-ads' ),
					'items'       => $this->get_schema_asset(),
					'default'     => [],
				],
			],
			$this->get_asset_group_fields()
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
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}


	/**
	 * Get Asset Groups by Campaign ID.
	 *
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
	 * Edit asset group.
	 *
	 * @return callable
	 */
	public function edit_asset_group_callback(): callable {
		return function( Request $request ) {
			try {
				$asset_group_fields = array_intersect_key(
					$request->get_params(),
					$this->get_asset_group_fields()
				);
				$asset_group_id     = $this->ads_asset_group->edit_asset_group( $request->get_param( 'id' ), $asset_group_fields, $request->get_param( 'assets' ) );
				return [
					'status'  => 'success',
					'message' => __( 'Successfully edited asset group.', 'google-listings-and-ads' ),
					'id'      => $asset_group_id,
				];
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
						'square_marketing_image'   => $this->get_schema_field_type_asset(),
						'marketing_image'          => $this->get_schema_field_type_asset(),
						'logo'                     => $this->get_schema_field_type_asset(),
						'business_name'            => $this->get_schema_field_type_asset(),
						'headline'                 => $this->get_schema_field_type_asset(),
						'description'              => $this->get_schema_field_type_asset(),
						'long_headline'            => $this->get_schema_field_type_asset(),
						'call_to_action_selection' => $this->get_schema_field_type_asset(),
					],
				],
			],

		];
	}

	/**
	 * Get the item schema for the field type asset.
	 *
	 * @return array the field type asset schema.
	 */
	protected function get_schema_field_type_asset(): array {
		return [
			'type'     => 'array',
			'items'    => $this->get_schema_asset(),
			'required' => false,
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
				'id'         => [
					'type'        => [ 'integer', 'null' ],
					'description' => __( 'Asset ID', 'google-listings-and-ads' ),
				],
				'content'    => [
					'type'        => [ 'string', 'null' ],
					'description' => __( 'Asset content', 'google-listings-and-ads' ),
				],
				'field_type' => [
					'type'        => 'string',
					'description' => __( 'Asset field type', 'google-listings-and-ads' ),
					'required'    => true,
					'context'     => [ 'edit' ],
					'enum'        => [
						AssetFieldType::HEADLINE,
						AssetFieldType::LONG_HEADLINE,
						AssetFieldType::DESCRIPTION,
						AssetFieldType::BUSINESS_NAME,
						AssetFieldType::MARKETING_IMAGE,
						AssetFieldType::SQUARE_MARKETING_IMAGE,
						AssetFieldType::LOGO,
						AssetFieldType::CALL_TO_ACTION_SELECTION,
					],
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
