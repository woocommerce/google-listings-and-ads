<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack
 */
class AccountController extends BaseController {

	use ControllerTrait;

	/**
	 * @var Manager
	 */
	protected $manager;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Manager    $manager
	 */
	public function __construct( RESTServer $server, Manager $manager ) {
		parent::__construct( $server );
		$this->manager = $manager;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'jetpack/connect',
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

		$this->register_route(
			'jetpack/connected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
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
			// Mark the plugin connection as enabled, in case it was disabled earlier.
			$this->manager->enable_plugin();

			// Register the site to wp.com.
			if ( ! $this->manager->is_registered() ) {
				$result = $this->manager->register();

				if ( is_wp_error( $result ) ) {
					return [
						'status'  => 'error',
						'message' => $result->get_error_message(),
					];
				}
			}

			// Get an authorization URL which will redirect back to our page.
			$redirect = admin_url( 'admin.php?page=wc-admin&path=/google/setup-mc' );
			$auth_url = $this->manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( [ 'from' => 'woocommerce-payments' ], $auth_url );

			return [
				'url' => $auth_url,
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
			$this->manager->remove_connection();

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the callback function to determine if Jetpack is currently connected.
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function() {
			return [ 'active' => $this->is_jetpack_connected() ];
		};
	}

	/**
	 * Determine whether Jetpack is connected.
	 *
	 * @return string
	 */
	protected function is_jetpack_connected(): string {
		return $this->manager->is_active() ? 'yes' : 'no';
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		return [
			'url' => [
				'type'        => 'string',
				'description' => __( 'The URL for making a connection to Jetpack (wordpress.com).', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_item_schema_name(): string {
		return 'jetpack_account';
	}
}
