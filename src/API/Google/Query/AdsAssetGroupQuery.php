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
	 *
	 * @param array $search_args List of search args, such as pageSize.
	 */
	public function __construct( array $search_args = [] ) {
		parent::__construct( 'asset_group' );
		$this->columns( [ 'asset_group.resource_name' ] );
		$this->search_args = $search_args;
	}
}
