<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track when the extension is loaded.
 *
 * DEMO CLASS
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class Loaded extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_extension_loaded', [ $this, 'track_loaded' ] );
	}

	/**
	 * Track when the extension is first installed.
	 */
	public function track_loaded() {
		$this->record_event( 'extension_loaded' );
	}
}
