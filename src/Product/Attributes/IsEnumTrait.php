<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Trait for enums
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
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

}
