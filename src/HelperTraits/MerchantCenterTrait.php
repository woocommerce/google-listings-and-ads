<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

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

		return false;
	}

	/**
	 * Get whether the language is supported by the Merchant Center
	 *
	 * TODO: actually determine if supported
	 *
	 * @param  string $language
	 * @return bool
	 */
	protected function is_language_supported( string $language = '' ): bool {
		// Default to base site language
		if ( empty( $language ) ) {
			$language = '';
		}

		return false;
	}
}
