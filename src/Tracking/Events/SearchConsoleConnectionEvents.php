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
	 * The action fires without arguments, and WordPress then passes an empty
	 * string as the first one, so this callback leaves the parameter untyped
	 * and coerces it.
	 *
	 * @param mixed $properties Optional additional properties to pass with the event.
	 */
	public function track_search_console_connected( $properties = [] ): void {
		$this->record_event( 'search_console_connected', is_array( $properties ) ? $properties : [] );
	}
}
