<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Brand
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class Brand extends AbstractAttribute {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'brand';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Brand', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'Brand of the product', 'google-listings-and-ads' );
	}

}
