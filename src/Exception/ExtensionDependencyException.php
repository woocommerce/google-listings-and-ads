<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExtensionDependencyException
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class ExtensionDependencyException extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a required plugin/extension isn't enabled.
	 *
	 * @param string $plugin_name The name of the missing required plugin.
	 *
	 * @return static
	 */
	public static function missing_required_plugin( string $plugin_name ): ExtensionDependencyException {
		return new static(
			sprintf(
				/* translators: 1 the missing plugin name */
				__( 'Google Listings and Ads requires %1$s to be enabled.', 'google-listings-and-ads' ),
				$plugin_name
			)
		);
	}
}
