<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\GenderInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits\IsEnumTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gender
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class Gender extends AbstractAttribute implements WithValueOptionsInterface, WithMappingInterface {

	use IsEnumTrait;

	/**
	 * Returns the attribute ID.
	 *
	 * Must be the same as a Google product's property name to be set automatically.
	 *
	 * @return string
	 *
	 * @see \Google\Service\ShoppingContent\Product for the list of properties.
	 */
	public static function get_id(): string {
		return 'gender';
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
	 * Return the attribute's input class. Must be an instance of `AttributeInputInterface`.
	 *
	 * @return string
	 *
	 * @see AttributeInputInterface
	 *
	 * @since 1.5.0
	 */
	public static function get_input_type(): string {
		return GenderInput::class;
	}

	/**
	 * Returns the attribute name
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Gender', 'google-listings-and-ads' );
	}
}
