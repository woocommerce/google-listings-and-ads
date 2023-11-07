<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

/**
 * InvalidProperty class.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidProperty extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception for a class property that should not be null.
	 *
	 * @param string $class_name The class name.
	 * @param string $property   The class property name.
	 *
	 * @return static
	 */
	public static function not_null( string $class_name, string $property ) {
		return new static(
			sprintf(
				'The class "%s" property "%s" must be set.',
				$class_name,
				$property
			)
		);
	}
}
