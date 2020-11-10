<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Trait AdminConditional
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
trait AdminConditional {

	/**
	 * Check whether this object is currently needed.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return is_admin();
	}
}
