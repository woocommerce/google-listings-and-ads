<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Tracking\Events;

use Automattic\WooCommerce\GoogleForWC\Tracking\Tracks;
use Automattic\WooCommerce\GoogleForWC\Tracking\TracksInterface;

/**
 * Trait EventHelper.
 *
 * @package Automattic\WooCommerce\GoogleForWC\Tracking
 */
trait EventHelper {

	/**
	 * The tracks object.
	 *
	 * @var TracksInterface
	 */
	protected $tracks = null;

	/**
	 * Set the Tracks object that will be used for tracking.
	 *
	 * @param TracksInterface $tracks
	 */
	public function set_tracks( TracksInterface $tracks ) {
		$this->tracks = $tracks;
	}

	/**
	 * Record an event using the Tracks instance.
	 *
	 * @param string $event_name
	 * @param array  $properties
	 */
	private function record_event( $event_name, $properties = [] ) {
		// Instantiate if none provided.
		if ( null === $this->tracks ) {
			$this->tracks = new Tracks();
		}

		$this->tracks->record_event( $event_name, $properties );
	}
}
