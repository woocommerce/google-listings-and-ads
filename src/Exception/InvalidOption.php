<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidOption
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidOption extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid option name is provided.
	 *
	 * @param string $name The option name.
	 *
	 * @return static
	 */
	public static function invalid_name( string $name ) {
		return new static( sprintf( 'The option name "%s" is not valid.', $name ) );
	}
}
