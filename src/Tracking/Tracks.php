<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Tracks as TracksProxy;

/**
 * Tracks implementation for Google Listings and Ads.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class Tracks implements TracksInterface {

	use PluginHelper;

	/**
	 * @var TracksProxy
	 */
	protected $tracks;

	/**
	 * Tracks constructor.
	 *
	 * @param TracksProxy $tracks The proxy tracks object.
	 */
	public function __construct( TracksProxy $tracks ) {
		$this->tracks = $tracks;
	}

	/**
	 * Record an event in Tracks - this is the preferred way to record events from PHP.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties Custom properties to send with the event.
	 */
	public function record_event( $event_name, $properties = [] ) {
		// Include base properties.
		$base_properties = [
			"{$this->get_slug()}_version" => $this->get_version(),
		];

		$properties      = array_merge( $base_properties, $properties );
		$full_event_name = "{$this->get_slug()}_{$event_name}";
		$this->tracks->record_event( $full_event_name, $properties );
	}
}
