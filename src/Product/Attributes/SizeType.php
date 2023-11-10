<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\SizeTypeInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits\IsEnumTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class SizeType
 *
 * @see https://support.google.com/merchants/answer/6324497
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class SizeType extends AbstractAttribute implements WithValueOptionsInterface, WithMappingInterface {

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
		return 'sizeType';
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
			'regular'   => __( 'Regular', 'google-listings-and-ads' ),
			'petite'    => __( 'Petite', 'google-listings-and-ads' ),
			'plus'      => __( 'Plus', 'google-listings-and-ads' ),
			'tall'      => __( 'Tall', 'google-listings-and-ads' ),
			'big'       => __( 'Big', 'google-listings-and-ads' ),
			'maternity' => __( 'Maternity', 'google-listings-and-ads' ),
		];
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
		return SizeTypeInput::class;
	}

	/**
	 * Returns the attribute name
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Size Type', 'google-listings-and-ads' );
	}
}
