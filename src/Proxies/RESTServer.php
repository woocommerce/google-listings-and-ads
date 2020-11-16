<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use WP_REST_Server;

/**
 * Class RESTServer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class RESTServer {

	/**
	 * The REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * RESTServer constructor.
	 *
	 * @param WP_REST_Server $server
	 */
	public function __construct( WP_REST_Server $server ) {
		$this->server = $server;
	}

	/**
	 * Register a REST route.
	 *
	 * @param string $namespace The route namespace.
	 * @param string $route     The route.
	 * @param array  $args      Arguments for the route.
	 * @param bool   $override  (Optional) Whether to override existing routes.
	 */
	public function register_route( string $namespace, string $route, array $args, bool $override = false ): void {
		// Clean up namespace and route.
		$namespace  = trim( $namespace, '/' );
		$route      = trim( $route, '/' );
		$full_route = "/{$namespace}/{$route}";

		// Set default args for route options.
		$defaults = [
			'methods'  => $this->server::READABLE,
			'callback' => null,
			'args'     => [],
		];

		$common_args = $args['args'] ?? [];
		unset( $args['args'] );

		foreach ( $args as $key => &$group ) {
			if ( ! is_numeric( $key ) ) {
				continue;
			}

			$group         = array_merge( $defaults, $group );
			$group['args'] = array_merge( $common_args, $group['args'] );
		}

		$this->server->register_route( $namespace, $full_route, $args, $override );
	}
}
