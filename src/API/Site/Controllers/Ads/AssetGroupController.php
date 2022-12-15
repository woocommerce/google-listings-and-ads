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
 * Class AssetGroupController
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
	 * BudgetRecommendationController constructor.
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
					'callback'            => $this->get_asset_groups_assets_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}


	/**
	 * @return callable
	 */
	protected function get_asset_groups_assets_callback(): callable {
		return function( Request $request ) {
			try {
				$campaign_id = absint( $request['id'] );
				return $this->ads_asset_group->get_asset_groups_by_campaign_id( $campaign_id );
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
		return [];
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
