<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

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

	/**
	 * Return an array of WooCommerce product types that this attribute can be applied to.
	 *
	 * @return array
	 */
	public static function get_applicable_product_types(): array {
		return [ 'simple', 'variation' ];
	}

	/**
	 * Return the input class used for this attribute.
	 *
	 * Must be an instance of InputInterface
	 *
	 * @return string FQN of the input class
	 *
	 * @see InputInterface
	 */
	public static function get_input_type(): string {
		return Select::class;
	}

}
