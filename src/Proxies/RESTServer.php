<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
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
	 * @param WP_REST_Server|null $server
	 */
	public function __construct( ?WP_REST_Server $server = null ) {
		$this->server = $server ?? rest_get_server();
	}

	/**
	 * Register a REST route.
	 *
	 * @param string $namespace The route namespace.
	 * @param string $route     The route.
	 * @param array  $args      Arguments for the route.
	 */
	public function register_route( string $namespace, string $route, array $args ): void {
		// Clean up namespace and route.
		$namespace  = trim( $namespace, '/' );
		$route      = trim( $route, '/' );
		$full_route = "/{$namespace}/{$route}";
		$this->server->register_route( $namespace, $full_route, $this->prepare_route_args( $args ) );
	}

	/**
	 * Prepare the route arguments.
	 *
	 * @param array $args The route args to prepare.
	 *
	 * @return array Prepared args.
	 */
	protected function prepare_route_args( array $args ): array {
		$defaults = [
			'methods'  => TransportMethods::READABLE,
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

		return $args;
	}
}
