<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

/**
 * Tracks interface for Google for WooCommerce.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
interface TracksInterface {

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 */
	public function record_event( $event_name, $properties = [] );
}
