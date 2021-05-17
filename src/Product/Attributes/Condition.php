<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Condition
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class Condition extends AbstractAttribute implements WithValueOptionsInterface {

	/**
	 * Returns the attribute ID.
	 *
	 * Must be the same as a Google product's property name to be set automatically.
	 *
	 * @return string
	 *
	 * @see \Google_Service_ShoppingContent_Product for the list of properties.
	 */
	public static function get_id(): string {
		return 'condition';
	}

	/**
	 * Returns a name for the attribute. Used in attribute's input.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Condition', 'google-listings-and-ads' );
	}

	/**
	 * Returns a short description for the attribute. Used in attribute's input.
	 *
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'Condition or state of the item.', 'google-listings-and-ads' );
	}

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array {
		return [ 'simple', 'variation' ];
	}

	/**
	 * Return an array of values available to choose for the attribute.
	 *
	 * Note: array key is used as the option key.
	 *
	 * @return array
	 */
	public static function get_value_options(): array {
		return [
			'new'         => __( 'New', 'google-listings-and-ads' ),
			'refurbished' => __( 'Refurbished', 'google-listings-and-ads' ),
			'used'        => __( 'Used', 'google-listings-and-ads' ),
		];
	}
}
