<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager
 */
class AccountController extends BaseController {

	/** @var Connection */
	protected $connection;

	/** @var TagManagerSiteTag */
	protected $site_tag;

	/**
	 * AccountController constructor.
	 *
	 * @param RESTServer        $server
	 * @param Connection        $connection
	 * @param TagManagerSiteTag $site_tag
	 */
	public function __construct( RESTServer $server, Connection $connection, TagManagerSiteTag $site_tag ) {
		parent::__construct( $server );

		$this->connection = $connection;
		$this->site_tag   = $site_tag;
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
				'schema' => $this->get_api_response_schema_callback(),
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
				'schema' => $this->get_api_response_schema_callback(),
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
				'schema' => $this->get_api_response_schema_callback(),
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
				return array_merge(
					$this->connection->get_status(),
					[ 'injectionFailed' => $this->site_tag->has_injection_failed() ]
				);
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
			} catch ( TagManagerApiException $e ) {
				return $this->response_from_tag_manager_exception( $e );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Shape a `TagManagerApiException` into the `code: 'API_ERROR'` response format the
	 * account-connect UI (`ConnectionErrorNotice` in JS) reads the specific backend message
	 * from — the same convention `MerchantCenter\AccountController` already uses for
	 * structured backend errors. The generic `response_from_exception()` fallback only
	 * returns a bare `message`, which that UI deliberately doesn't surface (see its own
	 * comment) since most other failure shapes there are synthesized, not from the backend.
	 *
	 * @param TagManagerApiException $e
	 *
	 * @return Response
	 */
	protected function response_from_tag_manager_exception( TagManagerApiException $e ): Response {
		return new Response(
			[
				'code'    => 'API_ERROR',
				'message' => $e->getMessage(),
				'data'    => [ 'message' => $e->getMessage() ],
			],
			$e->get_http_status()
		);
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
