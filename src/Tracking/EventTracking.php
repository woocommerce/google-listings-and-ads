<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\TracksEventInterface;


/**
 * Wire up the Google Listings and Ads events to Tracks.
 * Add all new events to `$events`.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class EventTracking implements Service, Registerable {
	/**
	 * The tracks object.
	 *
	 * @var TracksInterface
	 */
	private static $tracks;

	/**
	 * @var string[] Individual events classes to load.
	 */
	protected $events = [
		Events\Loaded::class,
	];

	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function() {
				$this->register_events();
			}
		);
	}

	/**
	 *
	 */
	public function register_events() {

		$this->maybe_initialize_tracks();

		// Instantiate each event class.
		foreach ( $this->events as $class ) {
//			self::validate_class( $class, Event_Tracker_Interface::class );

			/** @var TracksEventInterface $instance */
			$instance = new $class();
			$instance->register();
			$instance->set_tracks( self::$tracks );
		}
	}

	/**
	 * Initialize the tracks object if needed.
	 */
	private function maybe_initialize_tracks() {
		if ( null === self::$tracks ) {
			self::$tracks = new Tracks();
		}
	}

}
