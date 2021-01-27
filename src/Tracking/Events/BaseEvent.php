<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksAwareTrait;

/**
 * Class BaseEvent
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events
 */
abstract class BaseEvent implements TracksEventInterface, TracksAwareInterface {

	use TracksAwareTrait, PluginHelper;

	/**
	 * Record an event using the Tracks instance.
	 *
	 * @param string $event_name The event name to record.
	 * @param array  $properties (Optional) Properties to record with the event.
	 */
	protected function record_event( string $event_name, $properties = [] ) {
		$this->tracks->record_event( $event_name, $properties );
	}
}
