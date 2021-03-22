<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MerchantCenterTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait MerchantCenterTrait {

	use OptionsAwareTrait;
	use GoogleHelper;
	use ContainerAwareTrait;

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	protected function setup_complete(): bool {
		return boolval( $this->options->get( Options::MC_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param  string $country Optional - to check a country other than the site country.
	 * @return bool True if the country is in the list of MC-supported countries.
	 */
	protected function is_country_supported( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = $this->container->get( WC::class )->get_base_country();
		}

		return in_array(
			strtoupper( $country ),
			$this->get_mc_supported_countries(),
			true
		);
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
			$language = substr( $this->container->get( WP::class )->get_locale(), 0, 2 );
		}

		return in_array(
			strtolower( $language ),
			$this->get_mc_supported_languages(),
			true
		);
	}

	/**
	 * @return string[] List of target countries specified in options. Defaults to WooCommerce store base country.
	 */
	protected function get_target_countries(): array {
		$target_countries = [ $this->container->get( WC::class )->get_base_country() ];

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
