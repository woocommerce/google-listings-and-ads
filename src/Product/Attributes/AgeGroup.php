<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class AgeGroup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class AgeGroup extends AbstractAttribute implements WithValueOptionsInterface {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'age_group';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Age Group', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'Target age group of the item.', 'google-listings-and-ads' );
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
			'newborn' => __( 'Newborn', 'google-listings-and-ads' ),
			'infant'  => __( 'Infant', 'google-listings-and-ads' ),
			'toddler' => __( 'Toddler', 'google-listings-and-ads' ),
			'kids'    => __( 'Kids', 'google-listings-and-ads' ),
			'adult'   => __( 'Adult', 'google-listings-and-ads' ),
		];
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
