<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

/**
 * Class BaseEndpoint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
abstract class BaseController implements Registerable {

	/**
	 * @var RESTServer
	 */
	protected $server;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		$this->server = $server;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function() {
				$this->register_routes();
			}
		);
	}

	/**
	 * Register a single route.
	 *
	 * @param string $route The route name.
	 * @param array  $args  The arguments for the route.
	 */
	protected function register_route( string $route, array $args ) {
		$this->server->register_route( $this->get_namespace(), $route, $args );
	}

	/**
	 * Get the namespace for the current controller.
	 *
	 * @return string
	 */
	protected function get_namespace(): string {
		$namespace = str_replace( __NAMESPACE__, '', static::class );
		$namespace = str_replace( '\\', '/', $namespace );
		$namespace = strtolower( $namespace );

		return $namespace;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	abstract protected function register_routes(): void;
}
