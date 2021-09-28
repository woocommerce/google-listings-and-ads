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
	 * @var array The request SERVER variables.
	 */
	private $server_vars;

	/**
	 * Activated constructor.
	 *
	 * @param array $server_vars The request SERVER variables.
	 */
	public function __construct( array $server_vars ) {
		$this->server_vars = $server_vars;
	}

	/**
	 * Register the tracking class.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_extension_activated', [ $this, 'maybe_track_activation_source' ] );
	}

	/**
	 * Track when the extension is activated from a source.
	 */
	public function maybe_track_activation_source(): void {
		// Skip WP-CLI activations
		if ( empty( $this->server_vars['HTTP_REFERER'] ) ) {
			return;
		}

		$url_components = wp_parse_url( $this->server_vars['HTTP_REFERER'] );
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
