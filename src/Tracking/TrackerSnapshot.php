<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Tracking;

use Automattic\WooCommerce\GoogleForWC\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleForWC\Infrastructure\Service;
use Automattic\WooCommerce\GoogleForWC\PluginHelper;

/**
 * Include Google Listings and Ads data in the WC Tracker snapshot.
 *
 * @package Automattic\WooCommerce\GoogleForWC\Tracking
 */
class TrackerSnapshot implements Service, Registerable {

	use PluginHelper;

	/**
	 * Extension name to use for WC Tracker snapshot data.
	 */
	const EXTENSION_NAME = 'woogle';


	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		if ( 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			add_filter(
				'woocommerce_tracker_data',
				[ $this, 'add_snapshot_data' ],
				10,
				1
			);
		}
	}

	/**
	 * Add extension data to the WC Tracker snapshot.
	 *
	 * @param array $data The existing array of tracker data.
	 *
	 * @return array The updated array of tracker data.
	 */
	private function add_snapshot_data( $data = [] ) {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = [];
		}

		$data['extensions'][self::EXTENSION_NAME] = [
			'settings' => $this->get_settings(),
		];

		return $data;
	}

	/**
	 * Get general extension and settings data for the extension.
	 *
	 * @return array
	 */
	private function get_settings() {
		return [
			'version' => $this->get_version(),
		];
	}
}
