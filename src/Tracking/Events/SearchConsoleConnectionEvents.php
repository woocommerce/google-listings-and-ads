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
	 * @param mixed $properties Optional properties for the event. Untyped because the action passes none, and WordPress then sends an empty string.
	 */
	public function track_search_console_connected( $properties = [] ): void {
		$this->record_event( 'search_console_connected', is_array( $properties ) ? $properties : [] );
	}
}
