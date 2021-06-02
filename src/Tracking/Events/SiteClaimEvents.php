<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track for Site Claim actions:
 * - Site claim required
 * - Site claim success
 * - Site claim failure
 * - Merchant Center URL switch
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class SiteClaimEvents extends BaseEvent {

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'gla_site_claim_overwrite_required', [ $this, 'track_site_claim_overwrite_required' ] );
		add_action( 'gla_site_claim_success', [ $this, 'track_site_claim_success' ] );
		add_action( 'gla_site_claim_failure', [ $this, 'track_site_claim_failure' ] );
		add_action( 'gla_url_switch_required', [ $this, 'track_url_switch_required' ] );
		add_action( 'gla_url_switch_success', [ $this, 'track_url_switch_success' ] );
	}

	/**
	 * Track when a site claim needs to be overwritten.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_site_claim_overwrite_required( array $properties = [] ): void {
		$properties['action'] = 'overwrite_required';
		$this->track_site_claim_event( $properties );
	}

	/**
	 * Track when a site is claimed successfully.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_site_claim_success( array $properties = [] ): void {
		$properties['action'] = 'success';
		$this->track_site_claim_event( $properties );
	}

	/**
	 * Track when a site fails to be claimed.
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_site_claim_failure( array $properties = [] ): void {
		$properties['action'] = 'failure';
		$this->track_site_claim_event( $properties );
	}

	/**
	 * Track the generic site claim event with the action property.
	 *
	 * @param array $properties
	 */
	protected function track_site_claim_event( array $properties = [] ): void {
		$this->record_event( 'site_claim', $properties );
	}

	/**
	 * Track when a site requires a URL switch
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_url_switch_required( array $properties = [] ): void {
		$properties['action'] = 'required';
		$this->track_url_switch_event( $properties );
	}

	/**
	 * Track when a site executes a successful URL switch
	 *
	 * @param array $properties Optional additional properties to pass with the event.
	 */
	public function track_url_switch_success( array $properties = [] ): void {
		$properties['action'] = 'success';
		$this->track_url_switch_event( $properties );
	}

	/**
	 * Track the generic url switch event with the action property.
	 *
	 * @param array $properties
	 */
	protected function track_url_switch_event( array $properties = [] ): void {
		$this->record_event( 'mc_url_switch', $properties );
	}
}
