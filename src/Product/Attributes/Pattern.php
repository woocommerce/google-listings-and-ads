<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Pattern
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class Pattern extends AbstractAttribute {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'pattern';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Pattern', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'The item\'s pattern (e.g. polka dots).', 'google-listings-and-ads' );
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
