<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Tracking\Events;

use Automattic\WooCommerce\GoogleForWC\Tracking\TracksInterface;

/**
 * Interface describing an event tracker class.
 *
 * @package Automattic\WooCommerce\GoogleForWC\Tracking
 */
interface TracksEventInterface {

	/**
	 * Initialize the tracking class with various hooks.
	 */
	public function register();

	/**
	 * Set the Tracks object that will be used for tracking.
	 *
	 * @param TracksInterface $tracks
	 */
	public function set_tracks( TracksInterface $tracks );
}
