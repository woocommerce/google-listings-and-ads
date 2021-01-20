<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google
 */
class AccountController extends BaseController {

	use ControllerTrait;

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Connection $connection
	 */
	public function __construct( RESTServer $server, Connection $connection ) {
		parent::__construct( $server );
		$this->connection = $connection;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'google/connect',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_disconnect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
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
				'url' => $this->connection->connect( admin_url( 'admin.php?page=wc-admin&path=/google/setup-mc' ) ),
			];
		};
	}

	/**
	 * Get the callback function for the disconnection request.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function() {
			$this->connection->disconnect();

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		return [];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_item_schema_name(): string {
		return 'google_account';
	}
}
