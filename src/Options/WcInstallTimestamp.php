<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WPAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WPAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class WcInstallTimestamp
 *
 * Records the WooCommerce install timestamp once when WooCommerce is installed.
 * Backfills from WooCommerce core for stores that already had Woo before G4W.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class WcInstallTimestamp implements OptionsAwareInterface, Registerable, Service, WPAwareInterface {

	use OptionsAwareTrait;
	use WPAwareTrait;

	/**
	 * WooCommerce core option that stores the WooCommerce Admin install timestamp.
	 */
	protected const WC_ADMIN_INSTALL_TIMESTAMP_OPTION = 'woocommerce_admin_install_timestamp';

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_installed',
			function () {
				$this->record_install_timestamp();
			}
		);

		$this->maybe_backfill_wc_install_timestamp();
	}

	/**
	 * Store the WooCommerce install timestamp once.
	 */
	protected function record_install_timestamp(): void {
		$this->options->add( OptionsInterface::WC_INSTALL_TIMESTAMP, time() );
	}

	/**
	 * Backfill the WooCommerce install timestamp for stores that had Woo before G4W.
	 */
	protected function maybe_backfill_wc_install_timestamp(): void {
		if ( $this->options->get( OptionsInterface::WC_INSTALL_TIMESTAMP ) ) {
			return;
		}

		$wc_admin_timestamp = $this->wp->get_option( self::WC_ADMIN_INSTALL_TIMESTAMP_OPTION );

		if ( ! is_numeric( $wc_admin_timestamp ) || (int) $wc_admin_timestamp <= 0 ) {
			return;
		}

		$this->options->add( OptionsInterface::WC_INSTALL_TIMESTAMP, (int) $wc_admin_timestamp );
	}
}
