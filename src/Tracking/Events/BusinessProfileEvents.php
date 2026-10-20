<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track when a Google Business Profile location is connected, and when
 * sending a post to Google Business Profile succeeds or fails.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class BusinessProfileEvents extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_business_profile_location_connected', [ $this, 'track_location_connected' ] );
		add_action( 'woocommerce_gla_business_profile_sync_success', [ $this, 'track_sync_success' ] );
		add_action( 'woocommerce_gla_business_profile_sync_failure', [ $this, 'track_sync_failure' ] );
	}

	/**
	 * Track when a location is connected.
	 */
	public function track_location_connected(): void {
		$this->record_event( 'google_business_profile_location_connected' );
	}

	/**
	 * Track when Google accepts a post.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_sync_success( $properties = [] ): void {
		$this->record_event( 'google_business_profile_sync_success', is_array( $properties ) ? $properties : [] );
	}

	/**
	 * Track when sending a post to Google fails.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_sync_failure( $properties = [] ): void {
		$this->record_event( 'google_business_profile_sync_failure', is_array( $properties ) ? $properties : [] );
	}
}
