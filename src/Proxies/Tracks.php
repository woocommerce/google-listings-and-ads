<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use WC_Tracks;

/**
 * Class Tracks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class Tracks {

	/**
	 * Record a tracks event.
	 *
	 * @param string $name       The event name to record.
	 * @param array  $properties Array of properties to include with the event.
	 */
	public function record_event( string $name, array $properties = [] ): void {
		if ( class_exists( WC_Tracks::class ) ) {
			WC_Tracks::record_event( $name, $properties );
		} elseif ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event( $name, $properties );
		}
	}
}
