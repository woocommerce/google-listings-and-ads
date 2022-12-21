<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupAssetQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsAssetGroupAssetQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'asset_group_asset' );
		$this->columns( [ 'asset.id', 'asset.name', 'asset.type', 'asset.text_asset.text', 'asset.image_asset.full_size.url', 'asset.call_to_action_asset.call_to_action', 'asset_group_asset.field_type' ] );
	}
}
