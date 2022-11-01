<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait for enums
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits
 */
trait IsEnumTrait {

	/**
	 * Returns true for the is_enum property
	 *
	 * @return true
	 */
	public static function is_enum(): bool {
		return true;
	}

	/**
	 * Returns the attribute sources
	 *
	 * @return array
	 */
	public static function get_sources(): array {
		return self::get_value_options();
	}
}
