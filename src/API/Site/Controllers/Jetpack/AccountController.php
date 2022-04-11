<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Jetpack
 */
class AccountController extends BaseOptionsController {

	/**
	 * @var Manager
	 */
	protected $manager;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * Mapping between the client page name and its path.
	 * The first value is also used as a default,
	 * and changing the order of keys/values may affect things below.
	 *
	 * @var string[]
	 */
	private const NEXT_PATH_MAPPING = [
		'setup-mc'  => '/google/setup-mc',
		'reconnect' => '/google/settings&subpath=/reconnect-wpcom-account',
	];

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Manager    $manager
	 * @param Middleware $middleware
	 */
	public function __construct( RESTServer $server, Manager $manager, Middleware $middleware ) {
		parent::__construct( $server );
		$this->manager    = $manager;
		$this->middleware = $middleware;
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
					'args'                => $this->get_connect_params(),
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
		return function( Request $request ) {
			// Mark the plugin connection as enabled, in case it was disabled earlier.
			$this->manager->enable_plugin();

			// Register the site to wp.com.
			if ( ! $this->manager->is_connected() ) {
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
			$next     = $request->get_param( 'next_page_name' );
			$path     = self::NEXT_PATH_MAPPING[ $next ];
			$redirect = admin_url( "admin.php?page=wc-admin&path={$path}" );
			$auth_url = $this->manager->get_authorization_url( null, $redirect );

			// Payments flow allows redirect back to the site without showing plans.
			$auth_url = add_query_arg( [ 'from' => 'google-listings-and-ads' ], $auth_url );

			return [
				'url' => $auth_url,
			];
		};
	}

	/**
	 * Get the query params for the connection request.
	 *
	 * @return array
	 */
	protected function get_connect_params(): array {
		return [
			'context'        => $this->get_context_param( [ 'default' => 'view' ] ),
			'next_page_name' => [
				'description'       => __( 'Indicates the next page name mapped to the redirect URL when back from Jetpack authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => array_key_first( self::NEXT_PATH_MAPPING ),
				'enum'              => array_keys( self::NEXT_PATH_MAPPING ),
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the callback function for the disconnection request.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function() {
			$this->manager->remove_connection();
			$this->options->delete( OptionsInterface::WP_TOS_ACCEPTED );
			$this->options->delete( OptionsInterface::JETPACK_CONNECTED );

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
			if ( $this->is_jetpack_connected() && ! $this->options->get( OptionsInterface::WP_TOS_ACCEPTED ) ) {
				$this->log_wp_tos_accepted();
			}

			$user_data = $this->get_jetpack_user_data();
			return [
				'active'      => $this->display_boolean( $this->is_jetpack_connected() ),
				'owner'       => $this->display_boolean( $this->is_jetpack_connection_owner() ),
				'displayName' => $user_data['display_name'] ?? '',
				'email'       => $user_data['email'] ?? '',
			];
		};
	}

	/**
	 * Determine whether Jetpack is connected.
	 * Check if manager is active and we have a valid token.
	 *
	 * @return bool
	 */
	protected function is_jetpack_connected(): bool {
		if ( ! $this->manager->is_active() ) {
			return false;
		}

		return false !== $this->manager->get_tokens()->get_access_token();
	}

	/**
	 * Determine whether user is the current Jetpack connection owner.
	 *
	 * @return bool
	 */
	protected function is_jetpack_connection_owner(): bool {
		return $this->manager->is_connection_owner();
	}

	/**
	 * Format boolean for display.
	 *
	 * @param bool $value
	 *
	 * @return string
	 */
	protected function display_boolean( bool $value ): string {
		return $value ? 'yes' : 'no';
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
	 * Log accepted TOS for WordPress.
	 */
	protected function log_wp_tos_accepted() {
		$user = wp_get_current_user();
		$this->middleware->mark_tos_accepted( 'wp-com', $user->user_email );
		$this->options->update( OptionsInterface::WP_TOS_ACCEPTED, true );
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
