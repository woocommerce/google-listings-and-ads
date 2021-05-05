<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gender
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class Gender extends AbstractAttribute implements WithValueOptionsInterface {

	/**
	 * @return string
	 */
	public static function get_id(): string {
		return 'gender';
	}

	/**
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Gender', 'google-listings-and-ads' );
	}

	/**
	 * @return string
	 */
	public static function get_description(): string {
		return __( 'The gender for which your product is intended', 'google-listings-and-ads' );
	}

	/**
	 * @return array
	 */
	public static function get_value_options(): array {
		return [
			'male'   => __( 'Male', 'google-listings-and-ads' ),
			'female' => __( 'Female', 'google-listings-and-ads' ),
			'unisex' => __( 'Unisex', 'google-listings-and-ads' ),
		];
	}

}
