<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConsentModeSnippet
 *
 * The default gtag consent mode configuration, shared by the tags that need consent defaults published
 * before they load: the Google Ads tag and the Google Tag Manager container snippet.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
final class ConsentModeSnippet {

	/**
	 * Get the default consent mode configuration.
	 *
	 * @return string
	 */
	public static function get_config(): string {
		$consent_mode_snippet = "gtag( 'consent', 'default', {
				analytics_storage: 'denied',
				ad_storage: 'denied',
				ad_user_data: 'denied',
				ad_personalization: 'denied',
				region: ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'CH'],
				wait_for_update: 500,
			} );";

		/**
		 * Filters the default gtag consent mode configuration.
		 *
		 * @param string $consent_mode_snippet Default configuration with all the parameters `denied` for the EEA region.
		 */
		return apply_filters( 'woocommerce_gla_gtag_consent', $consent_mode_snippet );
	}
}
