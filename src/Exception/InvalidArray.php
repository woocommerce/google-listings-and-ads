<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidArray
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidArray extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Return a new instance of the exception when specific keys are missing from an array.
	 *
	 * @param string $method       The method where the keys were to be provided.
	 * @param array  $missing_keys The array of key names that were missing. This should be value-based.
	 *
	 * @return static
	 */
	public static function missing_keys( string $method, array $missing_keys ) {
		return new static(
			sprintf(
				'The array provided to %s was missing the following keys: %s',
				$method,
				join( ',', $missing_keys )
			)
		);
	}
}
