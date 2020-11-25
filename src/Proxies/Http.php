<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\WPErrorTrait;
use WP_Http;

/**
 * Class Http
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class Http {

	use WPErrorTrait;

	/**
	 * Default arguments for a request.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * @var WP_Http
	 */
	protected $http;

	/**
	 * Http constructor.
	 *
	 * @param WP_Http|null $http The WP_Http object.
	 */
	public function __construct( ?WP_Http $http = null ) {
		$this->http = $http ?? new WP_Http();
	}

	/**
	 * Default arguments to use for each request.
	 *
	 * @param array $defaults
	 */
	public function set_request_defaults( array $defaults ): void {
		$this->defaults = $defaults;
	}

	/**
	 * Make a request with the WP_Http object.
	 *
	 * @param string $url  The URL for the request.
	 * @param array  $args Arguments for the request.
	 *
	 * @return array
	 */
	public function request( string $url, array $args = [] ): array {
		$request = $this->http->request( $url, array_replace_recursive( $this->defaults, $args ) );
		$this->check_for_wp_error( $request );

		return $request;
	}

	/**
	 * Make a GET request.
	 *
	 * @param string $url  The URL for the request.
	 * @param array  $args Arguments for the request.
	 *
	 * @return array
	 */
	public function get( string $url, array $args = [] ): array {
		$args = array_replace_recursive( $args, [ 'method' => 'GET' ] );

		return $this->request( $url, $args );
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $url  The URL for the request.
	 * @param array  $args Arguments for the request.
	 *
	 * @return array
	 */
	public function post( string $url, array $args = [] ): array {
		$args = array_replace_recursive( $args, [ 'method' => 'POST' ] );

		return $this->request( $url, $args );
	}
}
