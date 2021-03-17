<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MerchantCenterTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait MerchantCenterTrait {

	use OptionsAwareTrait;
	use GoogleHelper;

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	protected function setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether the country is supported by the Merchant Center
	 *
	 * TODO: actually determine if supported
	 *
	 * @param  string $country
	 * @return bool
	 */
	protected function is_country_supported( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = '';
		}

		return true;
	}

	/**
	 * Get whether the language is supported by the Merchant Center.
	 *
	 * @param  string $language Optional - to check a language other than the site language.
	 * @return bool True if the language is in the list of MC-supported languages.
	 */
	protected function is_language_supported( string $language = '' ): bool {
		// Default to base site language
		if ( empty( $language ) ) {
			$language = substr( get_locale(), 0, 2 );
		}

		return in_array(
			strtolower( $language ),
			$this->get_mc_supported_languages(),
			true
		);
	}
}
