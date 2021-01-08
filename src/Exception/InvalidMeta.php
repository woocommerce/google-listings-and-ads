<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidMeta
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidMeta extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid meta key is provided.
	 *
	 * @param string $key The meta key.
	 *
	 * @return static
	 */
	public static function invalid_key( string $key ) {
		return new static( sprintf( 'The meta key "%s" is not valid.', $key ) );
	}
}
