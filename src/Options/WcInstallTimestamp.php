<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class WcInstallTimestamp
 *
 * Records the WooCommerce install timestamp once when WooCommerce is installed.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class WcInstallTimestamp implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

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
	}

	/**
	 * Store the WooCommerce install timestamp once.
	 */
	protected function record_install_timestamp(): void {
		$this->options->add( OptionsInterface::WC_INSTALL_TIMESTAMP, time() );
	}
}
