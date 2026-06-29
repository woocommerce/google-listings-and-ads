<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class WcInstallTimestamp
 *
 * Copies the WooCommerce install timestamp into a GLA option once GLA is active.
 * GLA requires WooCommerce, so this runs after both plugins are available and reads
 * WooCommerce's existing install timestamp rather than recording the current time.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class WcInstallTimestamp implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

	/**
	 * WooCommerce core option that stores the WooCommerce Admin install timestamp.
	 */
	private const WC_ADMIN_INSTALL_TIMESTAMP_OPTION = 'woocommerce_admin_install_timestamp';

	/**
	 * @var WP
	 */
	private $wp;

	/**
	 * WcInstallTimestamp constructor.
	 *
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// Backfill immediately when GLA loads so existing Woo stores are covered
		// without waiting for admin_init.
		$this->record_wc_install_timestamp();

		add_action( 'admin_init', [ $this, 'record_wc_install_timestamp' ] );
	}

	/**
	 * Copy the WooCommerce install timestamp into a GLA option once.
	 */
	public function record_wc_install_timestamp(): void {
		$this->maybe_record_wc_install_timestamp();
	}

	/**
	 * Store the WooCommerce install timestamp once, if not already recorded.
	 */
	private function maybe_record_wc_install_timestamp(): void {
		if ( $this->options->get( OptionsInterface::WC_INSTALL_TIMESTAMP ) ) {
			return;
		}

		$wc_install_timestamp = $this->wp->get_option( self::WC_ADMIN_INSTALL_TIMESTAMP_OPTION );

		if ( ! is_numeric( $wc_install_timestamp ) || (int) $wc_install_timestamp <= 0 ) {
			return;
		}

		$this->options->add( OptionsInterface::WC_INSTALL_TIMESTAMP, (int) $wc_install_timestamp );
	}
}
