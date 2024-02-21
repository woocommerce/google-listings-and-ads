<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds an action to track a generic event, which can be triggered by:
 * `do_action( 'woocommerce_gla_track_event', 'event_name', $properties )`
 *
 * @since 2.5.16
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class GenericEvents extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_track_event', [ $this, 'track_event' ], 10, 2 );
	}

	/**
	 * Track a generic event providing event name and optional list of properties.
	 *
	 * @param string $event_name Event name to record.
	 * @param array  $properties Optional additional properties to pass with the event.
	 */
	public function track_event( string $event_name, array $properties = [] ) {
		$this->record_event( $event_name, $properties );
	}
}
