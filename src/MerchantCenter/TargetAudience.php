<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

/**
 * Class TargetAudience.
 *
 * @since 1.12.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class TargetAudience implements Service {

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * TargetAudience constructor.
	 *
	 * @param WC               $wc
	 * @param OptionsInterface $options
	 * @param GoogleHelper     $google_helper
	 */
	public function __construct( WC $wc, OptionsInterface $options, GoogleHelper $google_helper ) {
		$this->wc            = $wc;
		$this->options       = $options;
		$this->google_helper = $google_helper;
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
			$target_countries = $this->google_helper->get_mc_supported_countries();
		} elseif ( 'selected' === $location && ! empty( $target_audience['countries'] ) ) {
			$target_countries = $target_audience['countries'];
		}

		return $target_countries;
	}

	/**
	 * Return the main target country (default Store country).
	 * If the store country is not included then use the first target country.
	 *
	 * @return string
	 */
	public function get_main_target_country(): string {
		$target_countries = $this->get_target_countries();
		$shop_country     = $this->wc->get_base_country();

		return in_array( $shop_country, $target_countries, true ) ? $shop_country : $target_countries[0];
	}
}
