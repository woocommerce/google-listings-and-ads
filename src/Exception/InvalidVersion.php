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
	 * Create a new instance of the exception when an invalid version is detected.
	 *
	 * @param string $requirement
	 * @param string $found_version
	 * @param string $minimum_version
	 *
	 * @return static
	 */
	public static function from_requirement( string $requirement, string $found_version, string $minimum_version ): InvalidVersion {
		return new static(
			sprintf(
				/* translators: 1 is the required component, 2 is the minimum required version, 3 is the version in use on the site */
				__( 'Google Listings and Ads requires %1$s version %2$s or higher. You are using version %3$s.', 'google-listings-and-ads' ),
				$requirement,
				$minimum_version,
				$found_version
			)
		);
	}

	/**
	 * Create a new instance of the exception when a requirement is missing.
	 *
	 * @param string $requirement
	 * @param string $minimum_version
	 *
	 * @return InvalidVersion
	 */
	public static function requirement_missing( string $requirement, string $minimum_version ): InvalidVersion {
		return new static(
			sprintf(
				/* translators: 1 is the required component, 2 is the minimum required version */
				__( 'Google Listings and Ads requires %1$s version %2$s or higher.', 'google-listings-and-ads' ),
				$requirement,
				$minimum_version
			)
		);
	}
}
