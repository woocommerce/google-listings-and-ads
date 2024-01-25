<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

/**
 * Trait for confirming tracking events are triggered.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait TrackingTrait {

	use WPHookTrait;

	/**
	 * Expect a track event to be triggered through a hook with specific event name and properties.
	 *
	 * @param string $event_name Event name to expect.
	 * @param array  $properties Properties to expect.
	 */
	protected function expect_track_event( string $event_name, array $properties = [] ) {
		$this->expect_do_action_once(
			'woocommerce_gla_track_event',
			[
				$event_name,
				$properties,
			]
		);
	}
}
