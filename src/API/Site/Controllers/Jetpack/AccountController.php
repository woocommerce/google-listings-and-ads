<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack
 */
class AccountController extends BaseController {

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
	public function register_routes(): void {
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
					return new Response(
						[
							'status'  => 'error',
							'message' => $result->get_error_message(),
						],
						400
					);
				}
			}

			// Get an authorization URL which will redirect back to our page.
			$redirect = admin_url( 'admin.php?page=wc-admin&path=/google/setup-mc' );
			$auth_url = $this->manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( [ 'from' => 'google-listings-and-ads' ], $auth_url );

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
			$user_data = $this->get_jetpack_user_data();
			return [
				'active'      => $this->is_jetpack_connected(),
				'owner'       => $this->is_jetpack_connection_owner(),
				'displayName' => $user_data['display_name'] ?? '',
				'email'       => $user_data['email'] ?? '',
			];
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
	 * Determine whether user is the current Jetpack connection owner.
	 *
	 * @return string
	 */
	protected function is_jetpack_connection_owner(): string {
		return $this->manager->is_connection_owner() ? 'yes' : 'no';
	}

	/**
	 * Get the wpcom user data of the current connected user.
	 *
	 * @return array
	 */
	protected function get_jetpack_user_data(): array {
		$user_data = $this->manager->get_connected_user_data();
		// adjust for $user_data returning false
		return is_array( $user_data ) ? $user_data : [];
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
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
	protected function get_schema_title(): string {
		return 'jetpack_account';
	}
}
