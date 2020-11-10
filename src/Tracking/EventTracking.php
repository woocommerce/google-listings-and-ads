<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\Loaded;
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
	protected $tracks;

	/**
	 * Individual events classes to load.
	 *
	 * @var string[]
	 */
	protected $events = [
		Loaded::class,
	];

	/**
	 * EventTracking constructor.
	 *
	 * @param TracksInterface $tracks The tracks interface object.
	 */
	public function __construct( TracksInterface $tracks ) {
		$this->tracks = $tracks;
	}

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
	 * Register all of our event tracking
	 */
	protected function register_events() {
		foreach ( $this->events as $class ) {
//			self::validate_class( $class, Event_Tracker_Interface::class );

			/** @var TracksEventInterface $instance */
			$instance = new $class();
			$instance->register();
			$instance->set_tracks( $this->tracks );
		}
	}
}
