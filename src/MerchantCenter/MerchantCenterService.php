<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MerchantCenterService implements Service {
	use GoogleHelper;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * @var WC
	 */
	protected $WC;

	/**
	 * @var WP
	 */
	protected $WP;

	/**
	 * MerchantCenterService constructor.
	 *
	 * @param OptionsInterface $options
	 * @param WC               $WC
	 * @param WP               $WP
	 */
	public function __construct( OptionsInterface $options, WC $WC, WP $WP ) {
		$this->options = $options;
		$this->WC      = $WC;
		$this->WP      = $WP;
	}

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( Options::MC_SETUP_COMPLETED_AT, false ) );
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param  string $country Optional - to check a country other than the site country.
	 * @return bool True if the country is in the list of MC-supported countries.
	 */
	public function is_country_supported( string $country = '' ): bool {
		// Default to WooCommerce store country
		if ( empty( $country ) ) {
			$country = $this->WC->get_base_country();
		}

		return array_key_exists(
			strtoupper( $country ),
			$this->get_mc_supported_countries_currencies()
		);
	}

	/**
	 * Get whether the language is supported by the Merchant Center.
	 *
	 * @param  string $language Optional - to check a language other than the site language.
	 * @return bool True if the language is in the list of MC-supported languages.
	 */
	public function is_language_supported( string $language = '' ): bool {
		// Default to base site language
		if ( empty( $language ) ) {
			$language = substr( $this->WP->get_locale(), 0, 2 );
		}

		return array_key_exists(
			strtolower( $language ),
			$this->get_mc_supported_languages()
		);
	}

	/**
	 * @return string[] List of target countries specified in options. Defaults to WooCommerce store base country.
	 */
	public function get_target_countries(): array {
		$target_countries = [ $this->WC->get_base_country() ];

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
