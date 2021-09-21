<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track when the extension is activated.
 * *
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class Activated extends BaseEvent {

	/**
	 * The page where activation with a source can occur.
	 */
	public const ACTIVATION_PAGE = 'plugin-install.php';

	/**
	 * The query parameters used to determine activation source details.
	 */
	public const SOURCE_PARAMS = [
		'traffic_source',
		'traffic_type',
	];

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_extension_activated', [ $this, 'maybe_track_activation_source' ] );
	}

	/**
	 * Track when the extension is activated from a source.
	 *
	 * @param array $server_vars The server variables to check for source information.
	 */
	public function maybe_track_activation_source( array $server_vars = [] ): void {
		// Default to $_SERVER
		if ( empty( $server_vars ) ) {
			$server_vars = $_SERVER;
		}

		// Skip WP-CLI activations
		if ( empty( $server_vars['HTTP_REFERER'] ) ) {
			return;
		}

		$url_components = wp_parse_url( $server_vars['HTTP_REFERER'] );
		// Only continue for Add Plugins page activations
		if ( false === strstr( $url_components['path'], self::ACTIVATION_PAGE ) || ! $url_components || empty( $url_components['query'] ) ) {
			return;
		}

		wp_parse_str( $url_components['query'], $query_vars );
		$available_source_params = array_intersect_key( $query_vars, array_flip( self::SOURCE_PARAMS ) );
		// Skip if no source params are present
		if ( empty( $available_source_params ) ) {
			return;
		}

		$this->record_event( 'activated_from_source', $available_source_params );
	}
}
