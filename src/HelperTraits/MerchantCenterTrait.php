<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
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

	/**
	 * @return string[] List of target countries specified in options. Defaults to WooCommerce store base country.
	 */
	protected function get_target_countries(): array {
		$target_countries = [ WC()->countries->get_base_country() ];

		$target_audience = $this->options->get( Options::TARGET_AUDIENCE );
		if ( empty( $target_audience['location'] ) && empty( $target_audience['countries'] ) ) {
			return $target_countries;
		}

		$location = strtolower( $target_audience['location'] );
		if ( 'all' === $location ) {
			$target_countries = $this->get_mc_supported_countries();
		} elseif ( 'selected' === $location && ! empty( $target_audience['countries'] ) ) {
			$target_countries = $target_audience['countries'];
		}

		return $target_countries;
	}
}
