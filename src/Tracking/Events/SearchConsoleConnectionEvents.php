<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds an action to track when a Search Console property is connected.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class SearchConsoleConnectionEvents extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_search_console_connected', [ $this, 'track_search_console_connected' ] );
	}

	/**
	 * Track when a Search Console property is connected.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_search_console_connected( array $properties = [] ): void {
		$this->record_event( 'search_console_connected', $properties );
	}
}
