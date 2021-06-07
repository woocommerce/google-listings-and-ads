<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExtensionRequirementException
 *
 * Error messages generated in this class should be translated, as they are intended to be displayed
 * to end users.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class ExtensionRequirementException extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a required plugin/extension isn't activated.
	 *
	 * @param string $plugin_name The name of the missing required plugin.
	 *
	 * @return static
	 */
	public static function missing_required_plugin( string $plugin_name ): ExtensionRequirementException {
		return new static(
			sprintf(
				/* translators: 1 the missing plugin name */
				__( 'Google Listings and Ads requires %1$s to be enabled.', 'google-listings-and-ads' ),
				$plugin_name
			)
		);
	}

	/**
	 * Create a new instance of the exception when an incompatible plugin/extension is activated.
	 *
	 * @param string $plugin_name The name of the incompatible plugin.
	 *
	 * @return static
	 */
	public static function incompatible_plugin( string $plugin_name ): ExtensionRequirementException {
		return new static(
			sprintf(
				/* translators: 1 the incompatible plugin name */
				__( 'Google Listings and Ads is incompatible with %1$s.', 'google-listings-and-ads' ),
				$plugin_name
			)
		);
	}
}
