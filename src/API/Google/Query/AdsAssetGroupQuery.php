<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGroupQuery
 *
 * @since 1.12.2
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsAssetGroupQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'asset_group' );
		$this->columns( [ 'asset_group.resource_name' ] );
	}
}
