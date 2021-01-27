<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track when Site Verification is attempted (succeeds/fails).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class SiteVerificationEvents extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( $this->get_slug() . '_site_verify_success', [ $this, 'track_site_verify_success' ] );
		add_action( $this->get_slug() . '_site_verify_failure', [ $this, 'track_site_verify_failure' ] );
	}

	/**
	 * Track when a site is verified
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_site_verify_success( array $properties = [] ): void {
		$this->record_event( 'site_verify_success', $properties );
	}

	/**
	 * Track when a site fails to be verified.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_site_verify_failure( array $properties = [] ): void {
		$this->record_event( 'site_verify_failure', $properties );
	}
}
