<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

/**
 * A class of utilities for dealing with arrays.
 *
 * @since 2.4.0
 */
class ArrayUtil {

	/**
	 * Remove empty values from array.
	 *
	 * @param array $strings A list of strings.
	 *
	 * @return array A list of strings without empty strings.
	 */
	public static function remove_empty_values( array $strings ): array {
		return array_values( array_filter( $strings ) );
	}
}
