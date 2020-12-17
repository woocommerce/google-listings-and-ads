<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ConnectionController extends BaseController {

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/connect',
			[
				[
					'methods'              => TransportMethods::READABLE,
					'callback'             => $this->get_connect_callback(),
					'permissions_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_connection_schema(),
			]
		);
	}

	/**
	 * Get the callback function for the connection request.
	 *
	 * @return callable
	 */
	protected function get_connect_callback(): callable {
		return function() {
			return [
				'url' => 'https://example.com',
			];
		};
	}

	/**
	 * @return array
	 */
	protected function get_connection_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'plugins',
			'type'       => 'object',
			'properties' => [
				'connectAction' => [
					'description' => __( 'Action that should be completed after connection.', 'google-listings-and-ads' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
				],
			],
		];
	}
}
