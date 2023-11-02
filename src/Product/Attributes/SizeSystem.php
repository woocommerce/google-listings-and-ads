<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input\SizeSystemInput;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits\IsEnumTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class SizeSystem
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class SizeSystem extends AbstractAttribute implements WithValueOptionsInterface, WithMappingInterface {

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
		return 'sizeSystem';
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
			'US'  => __( 'US', 'google-listings-and-ads' ),
			'EU'  => __( 'EU', 'google-listings-and-ads' ),
			'UK'  => __( 'UK', 'google-listings-and-ads' ),
			'DE'  => __( 'DE', 'google-listings-and-ads' ),
			'FR'  => __( 'FR', 'google-listings-and-ads' ),
			'IT'  => __( 'IT', 'google-listings-and-ads' ),
			'AU'  => __( 'AU', 'google-listings-and-ads' ),
			'BR'  => __( 'BR', 'google-listings-and-ads' ),
			'CN'  => __( 'CN', 'google-listings-and-ads' ),
			'JP'  => __( 'JP', 'google-listings-and-ads' ),
			'MEX' => __( 'MEX', 'google-listings-and-ads' ),
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
		return SizeSystemInput::class;
	}

	/**
	 * Returns the attribute name
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return __( 'Size System', 'google-listings-and-ads' );
	}
}
