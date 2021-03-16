<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidVersion
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidVersion extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid PHP version is detected.
	 *
	 * @param string $found_version
	 * @param string $minimum_version
	 *
	 * @return static
	 */
	public static function from_php_version( string $found_version, string $minimum_version ): InvalidVersion {
		return new static(
			sprintf(
				/* translators: 1 is the minimum required PHP version, 2 is the version in use on the site */
				__( 'Google Listings and Ads requires PHP version %1$s or higher. You are using version %2$s.', 'google-listings-and-ads' ),
				$minimum_version,
				$found_version
			)
		);
	}
}
