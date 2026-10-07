<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\TagManagerSiteTag;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\RefreshTagManagerAdsConversionConflict;
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

	/**
	 * Query arg tagging an OAuth return URL with which service's connect flow it belongs to.
	 * Shared across services riding the same underlying Google connection (see
	 * {@see Connection::get_connection_url()}) — Search Console's own controller tags its
	 * return URL with this same param, its own service id as the value.
	 */
	protected const GOOGLE_SERVICE_OAUTH_PARAM = 'google-service';

	/** This service's id as a `GOOGLE_SERVICE_OAUTH_PARAM` value. */
	protected const SERVICE_ID = 'tag-manager';

	/** Failure reasons reported when creating a container fails. */
	protected const REASON_INSUFFICIENT_SCOPE = 'insufficient_scope';
	protected const REASON_PERMISSION_DENIED  = 'permission_denied';
	protected const REASON_QUOTA_EXCEEDED     = 'quota_exceeded';
	protected const REASON_API_ERROR          = 'api_error';

	/** @var Connection */
	protected $connection;

	/** @var TagManagerSiteTag */
	protected $site_tag;

	/** @var Settings */
	protected $settings;

	/** @var JobRepository */
	protected $job_repository;

	/**
	 * AccountController constructor.
	 *
	 * @param RESTServer        $server
	 * @param Connection        $connection
	 * @param TagManagerSiteTag $site_tag
	 * @param Settings          $settings
	 * @param JobRepository     $job_repository
	 */
	public function __construct( RESTServer $server, Connection $connection, TagManagerSiteTag $site_tag, Settings $settings, JobRepository $job_repository ) {
		parent::__construct( $server );

		$this->connection     = $connection;
		$this->site_tag       = $site_tag;
		$this->settings       = $settings;
		$this->job_repository = $job_repository;
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
					'args'                => $this->get_connect_params(),
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
					'callback'            => $this->get_select_or_create_container_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_select_or_create_container_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
		$this->register_route(
			'tag-manager/settings',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_settings_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_update_settings_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_settings_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the connection request.
	 *
	 * Tags the return URL with `google-service=tag-manager`, a plain flow identifier (not
	 * an outcome — the shared Google connection's own `google-mc=connected`/error state on
	 * return is what actually says whether the OAuth succeeded). The shared Google connection
	 * (see {@see Connection::get_connection_url()}) is also used by Merchant Center's own
	 * connect flow, so without this marker the frontend can't tell which flow a return belongs to.
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
							self::GOOGLE_SERVICE_OAUTH_PARAM,
							self::SERVICE_ID,
							admin_url(
								'admin.php?page=wc-admin&path=/google/settings&section=accounts'
							)
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
			$this->get_ads_conversion_conflict_job()->unschedule();

			$message = $this->connection->disconnect();

			// The settings belong to this connection, so the next one starts from the defaults.
			$this->settings->delete();

			return [
				'status'  => 'success',
				'message' => $message,
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
			} catch ( TagManagerApiException $e ) {
				return $this->response_from_tag_manager_exception( $e );
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
				$this->get_ads_conversion_conflict_job()->unschedule();

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
	 * Shape a Tag Manager API error into the API_ERROR response the connect UI surfaces.
	 *
	 * @param TagManagerApiException $e
	 * @param array                  $extra_data Further fields to include in the response's `data`.
	 *
	 * @return Response
	 */
	private function response_from_tag_manager_exception( TagManagerApiException $e, array $extra_data = [] ): Response {
		return new Response(
			[
				'code'    => 'API_ERROR',
				'message' => $e->getMessage(),
				'data'    => array_merge( [ 'message' => $e->getMessage() ], $extra_data ),
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
			} catch ( TagManagerApiException $e ) {
				return $this->response_from_tag_manager_exception( $e );
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
				$this->get_ads_conversion_conflict_job()->schedule();

				return [
					'status'  => 'success',
					'message' => __( 'Successfully selected Tag Manager container.', 'google-listings-and-ads' ),
				];
			} catch ( TagManagerApiException $e ) {
				return $this->response_from_tag_manager_exception( $e );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for selecting an existing container or creating a new one.
	 *
	 * An `id` selects an existing container; a `name` creates one. Exactly one of the two is expected.
	 *
	 * @return callable
	 */
	protected function get_select_or_create_container_callback(): callable {
		return function ( Request $request ) {
			$id   = sanitize_text_field( (string) $request['id'] );
			$name = sanitize_text_field( (string) $request['name'] );

			if ( '' !== $id && '' === $name ) {
				return $this->get_select_container_callback()( $request );
			}

			if ( '' === $id && '' !== $name ) {
				return $this->get_create_container_callback()( $request );
			}

			return new Response(
				[ 'message' => __( 'Provide either the ID of a container to select or a name for a new one.', 'google-listings-and-ads' ) ],
				400
			);
		};
	}

	/**
	 * Get the callback function for creating a container and connecting it.
	 *
	 * A failure response carries a `reason` in its `data` so the connect UI can say why it failed:
	 * `insufficient_scope` when the connection can't create containers at all, `permission_denied`
	 * when the merchant's Tag Manager access doesn't allow it, `quota_exceeded`, or `api_error`.
	 *
	 * @return callable
	 */
	protected function get_create_container_callback(): callable {
		return function ( Request $request ) {
			try {
				if ( ! $this->connection->can_create_containers() ) {
					$message = __( 'The Google connection does not allow creating Tag Manager containers.', 'google-listings-and-ads' );

					return new Response(
						[
							'code'    => 'API_ERROR',
							'message' => $message,
							'data'    => [
								'message' => $message,
								'reason'  => self::REASON_INSUFFICIENT_SCOPE,
							],
						],
						403
					);
				}

				$this->connection->create_container( sanitize_text_field( (string) $request['name'] ) );
				$this->get_ads_conversion_conflict_job()->schedule();

				return [
					'status'  => 'success',
					'message' => __( 'Successfully created Tag Manager container.', 'google-listings-and-ads' ),
				];
			} catch ( TagManagerApiException $e ) {
				return $this->response_from_tag_manager_exception( $e, [ 'reason' => self::get_failure_reason( $e ) ] );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Name the cause of a failed Tag Manager API request, from its HTTP status.
	 *
	 * @param TagManagerApiException $e
	 *
	 * @return string One of the `REASON_*` constants.
	 */
	private static function get_failure_reason( TagManagerApiException $e ): string {
		switch ( $e->get_http_status() ) {
			case 403:
				return self::REASON_PERMISSION_DENIED;
			case 429:
				return self::REASON_QUOTA_EXCEEDED;
			default:
				return self::REASON_API_ERROR;
		}
	}

	/**
	 * Get the params for the select-or-create container request.
	 *
	 * @return array
	 */
	protected function get_select_or_create_container_params(): array {
		return [
			'id'   => [
				'type'        => 'string',
				'description' => __( 'The ID of an existing Tag Manager container to select.', 'google-listings-and-ads' ),
				'context'     => [ 'edit' ],
			],
			'name' => [
				'type'        => 'string',
				'description' => __( 'The name of a new Tag Manager container to create and select.', 'google-listings-and-ads' ),
				'context'     => [ 'edit' ],
			],
		];
	}

	/**
	 * Get the callback function for reading the Tag Manager settings.
	 *
	 * @return callable
	 */
	protected function get_settings_callback(): callable {
		return function () {
			return $this->get_settings();
		};
	}

	/**
	 * Get the callback function for updating the Tag Manager settings.
	 *
	 * Responds with the stored value rather than echoing the request, since saving an
	 * unchanged value reports no update.
	 *
	 * @return callable
	 */
	protected function get_update_settings_callback(): callable {
		return function ( Request $request ) {
			if ( empty( $this->connection->get_connection_data()['container_id'] ) ) {
				return new Response(
					[ 'message' => __( 'No Tag Manager container has been connected yet.', 'google-listings-and-ads' ) ],
					400
				);
			}

			$this->settings->set_snippet_injection_enabled( (bool) $request['snippet_injection_enabled'] );

			return $this->get_settings();
		};
	}

	/**
	 * Get the stored Tag Manager settings.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		return [
			'snippetInjectionEnabled' => $this->settings->is_snippet_injection_enabled(),
		];
	}

	/**
	 * Get the job that periodically re-checks the connected container for a Google Ads conversion tag.
	 *
	 * @return RefreshTagManagerAdsConversionConflict
	 */
	private function get_ads_conversion_conflict_job(): RefreshTagManagerAdsConversionConflict {
		return $this->job_repository->get( RefreshTagManagerAdsConversionConflict::class );
	}

	/**
	 * Get the params for the settings update request.
	 *
	 * @return array
	 */
	protected function get_settings_params(): array {
		return [
			'snippet_injection_enabled' => [
				'type'        => 'boolean',
				'description' => __( 'Whether the Google Tag Manager container snippet is added to the storefront.', 'google-listings-and-ads' ),
				'required'    => true,
			],
		];
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
