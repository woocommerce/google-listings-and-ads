<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidArgument
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidArgument extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an argument value is not an object.
	 *
	 * @param string $name   The name of the argument.
	 * @param string $method The name of the method/function.
	 *
	 * @return static
	 */
	public static function not_object( string $name, string $method ) {
		return new static( sprintf( 'The argument "%s" provided to the function "%s" must be an object.', $name, $method ) );
	}
}
