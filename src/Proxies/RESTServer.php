<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

/**
 * Class RESTServer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class RESTServer {

	/**
	 * The REST server instance.
	 *
	 * @var Server
	 */
	protected $server;

	/**
	 * RESTServer constructor.
	 *
	 * @param Server|null $server
	 */
	public function __construct( ?Server $server = null ) {
		$this->server = $server ?? rest_get_server();
	}

	/**
	 * Register a REST route.
	 *
	 * @param string $route_namespace The route namespace.
	 * @param string $route           The route.
	 * @param array  $args            Arguments for the route.
	 */
	public function register_route( string $route_namespace, string $route, array $args ): void {
		// Clean up namespace and route.
		$route_namespace = trim( $route_namespace, '/' );
		$route           = trim( $route, '/' );
		$full_route      = "/{$route_namespace}/{$route}";
		$this->server->register_route( $route_namespace, $full_route, $this->prepare_route_args( $args ) );
	}

	/**
	 * Get the registered REST routes.
	 *
	 * @param string $route_namespace Optionally, only return routes in the given namespace.
	 * @return array `'/path/regex' => array( $callback, $bitmask )` or
	 *               `'/path/regex' => array( array( $callback, $bitmask ), ...)`.
	 *
	 * @since 1.4.0
	 */
	public function get_routes( string $route_namespace = '' ): array {
		return $this->server->get_routes( $route_namespace );
	}

	/**
	 * Run an internal request.
	 *
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function dispatch_request( Request $request ): Response {
		return $this->server->dispatch( $request );
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
