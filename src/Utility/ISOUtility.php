<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\Exception\ISO3166Exception;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;

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
	 * @var ISO3166DataProvider
	 */
	protected $iso3166_data_provider;

	/**
	 * ISOUtility constructor.
	 *
	 * @param ISO3166DataProvider $iso3166_data_provider
	 */
	public function __construct( ISO3166DataProvider $iso3166_data_provider ) {
		$this->iso3166_data_provider = $iso3166_data_provider;
	}

	/**
	 * Validate that the provided input is valid ISO 3166-1 alpha-2 country code.
	 *
	 * @param string $country_code
	 *
	 * @return bool
	 *
	 * @see https://wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements ISO 3166-1 alpha-2
	 *      officially assigned codes.
	 */
	public function is_iso3166_alpha2_country_code( string $country_code ): bool {
		try {
			$this->iso3166_data_provider->alpha2( $country_code );

			return true;
		} catch ( ISO3166Exception $exception ) {
			return false;
		}
	}

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
