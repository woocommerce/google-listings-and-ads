<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class SizeType
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class SizeType extends AbstractAttribute implements WithValueOptionsInterface {

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
		return 'sizeType';
	}

	/**
	 * Returns a name for the attribute. Used in attribute's input.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Size type', 'google-listings-and-ads' );
	}

	/**
	 * Returns a short description for the attribute. Used in attribute's input.
	 *
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'The cut of the item. Recommended for apparel items.', 'google-listings-and-ads' );
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
			'regular'      => __( 'Regular', 'google-listings-and-ads' ),
			'petite'       => __( 'Petite', 'google-listings-and-ads' ),
			'plus'         => __( 'Plus', 'google-listings-and-ads' ),
			'oversize'     => __( 'Oversize', 'google-listings-and-ads' ),
			'big and tall' => __( 'Big and tall', 'google-listings-and-ads' ),
			'maternity'    => __( 'Maternity', 'google-listings-and-ads' ),
		];
	}
}
