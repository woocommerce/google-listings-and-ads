<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidValue
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidValue extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a value is not a positive integer.
	 *
	 * @param string $method The method that requires a positive integer.
	 *
	 * @return static
	 */
	public static function negative_integer( string $method ) {
		return new static( sprintf( 'The method "%s" requires a positive integer value.', $method ) );
	}
}
