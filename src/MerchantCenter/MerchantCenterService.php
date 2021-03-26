<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;

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
	 * WooCommerce proxy service
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * WordPress proxy service
	 *
	 * @var WP
	 */
	protected $wp;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	/**
	 * MerchantCenterService constructor.
	 *
	 * @param OptionsInterface    $options
	 * @param WC                  $wc
	 * @param WP                  $wp
	 * @param Middleware          $middleware
	 * @param TransientsInterface $transients
	 */
	public function __construct( OptionsInterface $options, WC $wc, WP $wp, Middleware $middleware, TransientsInterface $transients ) {
		$this->options    = $options;
		$this->wc         = $wc;
		$this->wp         = $wp;
		$this->middleware = $middleware;
		$this->transients = $transients;
	}

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	public function is_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false ) );
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
			$country = $this->wc->get_base_country();
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
			$language = substr( $this->wp->get_locale(), 0, 2 );
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
		$target_countries = [ $this->wc->get_base_country() ];

		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE );
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

	/**
	 * Disconnect Merchant Center account
	 */
	public function disconnect() {
		$this->middleware->disconnect_merchant();

		$this->options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
		$this->options->delete( OptionsInterface::MC_SETUP_SAVED_STEP );
		$this->options->delete( OptionsInterface::MERCHANT_ACCOUNT_STATE );
		$this->options->delete( OptionsInterface::MERCHANT_CENTER );
		$this->options->delete( OptionsInterface::SITE_VERIFICATION );
		$this->options->delete( OptionsInterface::TARGET_AUDIENCE );

		$this->transients->delete( TransientsInterface::MC_PRODUCT_STATISTICS );
	}
}
