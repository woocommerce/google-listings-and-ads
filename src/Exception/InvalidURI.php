<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

/**
 * Class InvalidURI
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidURI extends InvalidArgumentException implements GoogleForWCException {

	/**
	 * Create a new exception for an unreadable asset.
	 *
	 * @param string $path
	 *
	 * @return static
	 */
	public static function asset_path( string $path ) {
		return new static( sprintf( 'The asset "%s" is unreadable. Do build scripts need to be run?', $path ) );
	}
}
