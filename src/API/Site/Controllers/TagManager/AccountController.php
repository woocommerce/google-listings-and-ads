<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager
 */
class AccountController extends BaseController {

	/** @var Connection */
	protected $connection;

	/**
	 * AccountController constructor.
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
			'tag-manager/connect',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
		$this->register_route(
			'tag-manager/connection',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_disconnect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
		$this->register_route(
			'tag-manager/accounts',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_accounts_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_select_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
			]
		);
		$this->register_route(
			'tag-manager/containers',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_containers_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_select_container_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
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
		return function () {
			try {
				return [
					'url' => $this->connection->connect(
						admin_url( 'admin.php?page=wc-admin&path=/google/settings' )
					),
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the connection status request.
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function () {
			try {
				return $this->connection->get_status();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the disconnection request.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function () {
			return [
				'status'  => 'success',
				'message' => $this->connection->disconnect(),
			];
		};
	}

	/**
	 * Get the callback function for listing the connected Google user's accounts.
	 *
	 * @return callable
	 */
	protected function get_accounts_callback(): callable {
		return function () {
			try {
				return $this->connection->list_accounts();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for selecting an account.
	 *
	 * @return callable
	 */
	protected function get_select_account_callback(): callable {
		return function ( Request $request ) {
			try {
				$this->connection->select_account( sanitize_text_field( (string) $request['id'] ) );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully selected Tag Manager account.', 'google-listings-and-ads' ),
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for listing the selected account's containers.
	 *
	 * @return callable
	 */
	protected function get_containers_callback(): callable {
		return function () {
			try {
				return $this->connection->list_containers();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for selecting a container.
	 *
	 * @return callable
	 */
	protected function get_select_container_callback(): callable {
		return function ( Request $request ) {
			try {
				$this->connection->select_container( sanitize_text_field( (string) $request['id'] ) );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully selected Tag Manager container.', 'google-listings-and-ads' ),
				];
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
			'id' => [
				'type'        => 'string',
				'description' => __( 'The Tag Manager account or container ID to select.', 'google-listings-and-ads' ),
				'context'     => [ 'edit' ],
				'required'    => true,
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
		return 'tag_manager_account';
	}
}
