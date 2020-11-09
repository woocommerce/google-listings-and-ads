<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Tracking;

use Automattic\WooCommerce\GoogleForWC\PluginHelper;
use WC_Tracks;
use WC_Site_Tracking;

/**
 * Tracks implementation for Google Listings and Ads.
 */
class GoogleForWCTracks {
	use PluginHelper;

	/**
	 * Tracks event name prefix (should end with '_').
	 */
	const PREFIX = 'woogle_';

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 */
	public function record_event( $event_name, $properties = array() ) {
		if ( ! class_exists( 'WC_Tracks' ) || ! WC_Site_Tracking::is_tracking_enabled() ) {
			return;
		}

		// Include base properties.
		$base_properties = array(
			self::PREFIX . '_version'    => $this->get_version(),
		);

		$properties = array_merge( $base_properties, $properties );
		$full_event_name = self::PREFIX . $event_name;
		WC_Tracks::record_event( $full_event_name, $properties );
	}
}
