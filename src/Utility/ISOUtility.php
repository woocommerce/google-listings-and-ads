<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class ISOUtility
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 *
 * @since 1.5.0
 */
class ISOUtility implements Service {

	/**
	 * Converts WordPress language code to IETF BCP 47 format.
	 *
	 * @param string $wp_locale
	 *
	 * @return string IETF BCP 47 language code or 'en-US' if the language code cannot be converted.
	 *
	 * @see https://tools.ietf.org/html/bcp47 IETF BCP 47 language codes.
	 */
	public function wp_locale_to_bcp47( string $wp_locale ): string {
		if ( empty( $wp_locale ) || ! preg_match( '/^[-_a-zA-Z0-9]{2,}$/', $wp_locale, $matches ) ) {
			return 'en-US';
		}

		return str_replace( '_', '-', $wp_locale );
	}
}
