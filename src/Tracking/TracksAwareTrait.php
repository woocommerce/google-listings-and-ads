<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking;

/**
 * Trait TracksAwareness
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
trait TracksAwareTrait {

	/**
	 * The tracks object.
	 *
	 * @var TracksInterface
	 */
	protected $tracks;

	/**
	 * Set the tracks interface object.
	 *
	 * @param TracksInterface $tracks
	 */
	public function set_tracks( TracksInterface $tracks ): void {
		$this->tracks = $tracks;
	}
}
