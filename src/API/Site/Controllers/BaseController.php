<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

/**
 * Class BaseEndpoint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
abstract class BaseController implements Registerable {

	use PluginHelper;

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
		$this->register_routes();
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
		return "wc/{$this->get_slug()}";
	}

	/**
	 * Register rest routes with WordPress.
	 */
	abstract protected function register_routes(): void;
}
