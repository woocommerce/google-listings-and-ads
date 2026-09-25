<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsAssetQuery extends AdsQuery {

	/**
	 * AdsAssetQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'asset' );
		$this->columns(
			[
				'asset.id',
				'asset.type',
			]
		);
	}
}
