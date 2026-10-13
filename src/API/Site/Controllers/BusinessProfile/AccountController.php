<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * Connects, reports, and disconnects the merchant's Business Profile.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile
 */
class AccountController extends BaseController {

	/**
	 * Query arg naming which service's connect flow an OAuth return URL belongs to.
	 * Merchant Center's connect flow uses the same shared Google connection.
	 */
	protected const GOOGLE_SERVICE_OAUTH_PARAM = 'google-service';

	/** Query arg naming the account card to scroll into view on Settings > Accounts. */
	protected const FOCUS_ACCOUNT_CARD_PARAM = 'focus-account-card';

	/** This service's ID as a query arg value. */
	protected const SERVICE_ID = 'business-profile';

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
			'business-profile/connect',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connect_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_connect_params(),
				],
				'schema' => $this->get_connect_schema_callback(),
			]
		);
		$this->register_route(
			'business-profile/connection',
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
	}

	/**
	 * Get the callback function for the connection request.
	 *
	 * Tags the return URL with `google-service=business-profile`, so the frontend can
	 * tell this flow apart from other flows on the shared Google connection, and with
	 * `focus-account-card=business-profile`, so the card scrolls into view on return.
	 *
	 * @return callable
	 */
	protected function get_connect_callback(): callable {
		return function ( Request $request ) {
			try {
				$login_hint = $request->get_param( 'login_hint' ) ?: '';

				return [
					'url' => $this->connection->connect(
						add_query_arg(
							[
								self::GOOGLE_SERVICE_OAUTH_PARAM => self::SERVICE_ID,
								self::FOCUS_ACCOUNT_CARD_PARAM   => self::SERVICE_ID,
							],
							admin_url( 'admin.php?page=wc-admin&path=/google/settings&section=accounts' )
						),
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
			'login_hint' => [
				'description'       => __( 'Indicate the Google account to suggest for authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					return is_string( $value ) && is_email( $value );
				},
				'sanitize_callback' => 'sanitize_email',
			],
		];
	}

	/**
	 * Get a callback function for returning the connect route's schema.
	 *
	 * The connect route returns an OAuth URL, not the connection status the
	 * controller's own schema describes.
	 *
	 * @return callable
	 */
	protected function get_connect_schema_callback(): callable {
		return function () {
			return $this->prepare_item_schema(
				[
					'url' => [
						'type'        => 'string',
						'description' => __( 'The URL for granting Google Business Profile access.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
				],
				'business_profile_connect'
			);
		};
	}

	/**
	 * Get the callback function for the connection status request.
	 *
	 * @return callable
	 */
	protected function get_connected_callback(): callable {
		return function () {
			return $this->connection->get_status();
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
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'status'    => [
				'type'        => 'string',
				'description' => __( 'Connection status.', 'google-listings-and-ads' ),
				'enum'        => [ Connection::STATUS_CONNECTED, Connection::STATUS_DISCONNECTED ],
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'id'        => [
				'type'        => 'string',
				'description' => __( 'Connected location ID.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'accountId' => [
				'type'        => 'string',
				'description' => __( 'ID of the Google Business Profile account the location is listed under.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'title'     => [
				'type'        => 'string',
				'description' => __( 'Location name.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'address'   => [
				'type'        => 'string',
				'description' => __( 'Location address on one line. Empty for a location without a storefront address.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'placeId'   => [
				'type'        => 'string',
				'description' => __( 'Google Maps place ID. Empty when Google has none for the location.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'mapsUri'   => [
				'type'        => 'string',
				'description' => __( 'Link to the location on Google Maps.', 'google-listings-and-ads' ),
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
		return 'business_profile_account';
	}
}
