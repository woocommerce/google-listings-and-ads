<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google
 */
class AccountController extends BaseController {

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Mapping between the client page name and its path.
	 * The first value is also used as a default,
	 * and changing the order of keys/values may affect things below.
	 *
	 * @var string[]
	 */
	private const NEXT_PATH_MAPPING = [
		'setup-mc'  => '/google/setup-mc',
		'setup-ads' => '/google/setup-ads',
		'reconnect' => '/google/settings&subpath=/reconnect-google-account',
	];

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
	public function register_routes(): void {
		$this->register_route(
			'google/connect',
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
			'google/connected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
		$this->register_route(
			'google/reconnected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_reconnected_callback(),
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
		return function ( Request $request ) {
			try {
				$next       = $request->get_param( 'next_page_name' );
				$login_hint = $request->get_param( 'login_hint' ) ?: '';
				$path       = self::NEXT_PATH_MAPPING[ $next ];
				return [
					'url' => $this->connection->connect(
						admin_url( "admin.php?page=wc-admin&path={$path}" ),
						$login_hint
					),
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
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
				'description'       => __( 'Indicates the next page name mapped to the redirect URL when back from Google authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => array_key_first( self::NEXT_PATH_MAPPING ),
				'enum'              => array_keys( self::NEXT_PATH_MAPPING ),
				'validate_callback' => 'rest_validate_request_arg',
			],
			'login_hint'     => [
				'description'       => __( 'Indicate the Google account to suggest for authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'is_email',
			],
		];
	}

	/**
	 * Get the callback function for the disconnection request.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function () {
			$this->connection->disconnect();

			return [
				'status'  => 'success',
				'message' => __( 'Successfully disconnected.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Get the callback function to determine if Google is currently connected.
	 *
	 * Uses consistent properties to the Jetpack connected callback
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function () {
			try {
				$status = $this->connection->get_status();
				return [
					'active' => array_key_exists( 'status', $status ) && ( 'connected' === $status['status'] ) ? 'yes' : 'no',
					'email'  => array_key_exists( 'email', $status ) ? $status['email'] : '',
					'scope'  => array_key_exists( 'scope', $status ) ? $status['scope'] : [],
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function to determine if we have access to the dependent services.
	 *
	 * @return callable
	 */
	protected function get_reconnected_callback(): callable {
		return function () {
			try {
				$status           = $this->connection->get_reconnect_status();
				$status['active'] = array_key_exists( 'status', $status ) && ( 'connected' === $status['status'] ) ? 'yes' : 'no';
				unset( $status['status'] );

				return $status;
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
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
				'description' => __( 'The URL for making a connection to Google.', 'google-listings-and-ads' ),
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
		return 'google_account';
	}
}
