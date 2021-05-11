<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class GTIN
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class GTIN extends AbstractAttribute {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'gtin';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Global Trade Item Number (GTIN)', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'Global Trade Item Number (GTIN) for your item. These identifiers include UPC (in North America), EAN (in Europe), JAN (in Japan), and ISBN (for books)', 'google-listings-and-ads' );
	}

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array {
		return [ 'simple', 'variation' ];
	}

}
