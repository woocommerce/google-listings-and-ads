<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events;

/**
 * This class adds actions to track when the extension is activated.
 * *
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tracking
 */
class ActivatedEvents extends BaseEvent {

	/**
	 * The page where activation with a source can occur.
	 */
	public const ACTIVATION_PAGE = 'plugin-install.php';

	/**
	 * The query parameters used to determine activation source details.
	 */
	public const SOURCE_PARAMS = [
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
	];

	/**
	 * @var array The request SERVER variables.
	 */
	private $server_vars;

	/**
	 * ActivatedEvents constructor.
	 *
	 * @param array $server_vars The request SERVER variables.
	 */
	public function __construct( array $server_vars ) {
		$this->server_vars = $server_vars;
	}

	/**
	 * Nothing to register (method invoked manually).
	 */
	public function register(): void {}

	/**
	 * Track when the extension is activated from a source.
	 */
	public function maybe_track_activation_source(): void {
		// Skip WP-CLI activations
		if ( empty( $this->server_vars['HTTP_REFERER'] ) ) {
			return;
		}

		$url_components = wp_parse_url( $this->server_vars['HTTP_REFERER'] );
		// Skip invalid URLs or URLs missing parts
		if ( ! is_array( $url_components ) || empty( $url_components['query'] ) || empty( $url_components['path'] ) ) {
			return;
		}

		// Skip activations from anywhere except the Add Plugins page
		if ( false === strstr( $url_components['path'], self::ACTIVATION_PAGE ) ) {
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
