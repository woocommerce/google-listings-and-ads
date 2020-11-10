<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure;

/**
 * Conditional interface.
 *
 * This interface allows objects to be instantiated only as needed. A static method is used to
 * determine whether an object needs to be instantiated. This prevents needless instantiation of
 * objects that won't be used in the current request.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure
 */
interface Conditional {

	/**
	 * Check whether this object is currently needed.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool;
}
