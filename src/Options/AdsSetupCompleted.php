<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsSetupCompleted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class AdsSetupCompleted implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

	protected const OPTION = OptionsInterface::ADS_SETUP_COMPLETED_AT;

	/**
	 * Register a service.
	 *
	 * TODO: call `do_action( 'woocommerce_gla_ads_settings_sync' );` when the initial Google Ads account,
	 *       paid campaign, and billing setup is completed.
	 */
	public function register(): void {
		add_action(
			'woocommerce_gla_ads_setup_completed',
			function () {
				$this->set_completed_timestamp();
				$this->enable_gtg_and_complete_tour();
			}
		);
	}

	/**
	 * Set the timestamp when setup was completed.
	 */
	protected function set_completed_timestamp() {
		$this->options->update( self::OPTION, time() );
	}

	/**
	 * Enables Google Tag Gateway and mark the tour as completed.
	 */
	protected function enable_gtg_and_complete_tour() {
		$is_gtg_configured = $this->options->get( OptionsInterface::ADS_GTG_ENABLED );

		if ( null === $is_gtg_configured ) {
			$this->options->update( OptionsInterface::ADS_GTG_ENABLED, true );
		}

		$tours = $this->options->get( OptionsInterface::TOURS, [] );
		if ( is_array( $tours ) ) {
			$tours['google-tag-gateway-tour'] = true;
			$this->options->update( OptionsInterface::TOURS, $tours );
		}
	}
}
