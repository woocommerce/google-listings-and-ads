<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

/**
 * Interface TracksAwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
interface TracksAwareInterface {

	/**
	 * Set the tracks interface object.
	 *
	 * @param TracksInterface $tracks
	 */
	public function set_tracks( TracksInterface $tracks ): void;
}
