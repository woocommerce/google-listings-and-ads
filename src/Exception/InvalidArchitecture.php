<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidArchitecture. Exceptions related to PHP architecture.
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @since x.x.x
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidArchitecture extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid architecture is detected.
	 *
	 * @since x.x.x
	 * @return static
	 */
	public static function invalid_architecture_bits(): InvalidArchitecture {
		return new static(
			__( 'Google Listings and Ads requires a 64 bit version of PHP.', 'google-listings-and-ads' )
		);
	}
}
