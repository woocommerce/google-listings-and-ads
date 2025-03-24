<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCountryQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCountryQuery extends AdsQuery {

	/**
	 * AdsCountryQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'geo_target_constant' );
		$this->columns(
			[
				'geo_target_constant.id',
				'geo_target_constant.country_code',
				'geo_target_constant.target_type',
			]
		);

		$this->where( 'geo_target_constant.target_type', 'Country' );
	}
}
