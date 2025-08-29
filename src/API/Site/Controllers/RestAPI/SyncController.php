<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncController
 *
 * GET wp-json/wc/gla/sync
 * POST wp-json/wc/gla/sync
 * Body Params: @see get_schema_properties
 *
 * We support 4 data types Products, Coupons, Shipping and Settings.
 * Each of this data types contains pull and push methods.
 *
 * Push: Legacy method pushing store data to Google Merchant Center using Google's API
 * Pull: New method where Google fetches the data from the store.
 *
 * All the data types and methods are optional. When set, they update the current config.
 *
 * Examples
 *
 * POST wp-json/wc/gla/sync
 *
 * {
 *     "products": {
 *          "pull": true,
 *          "push": false,
 *     },
 *     "settings": {
 *         "pull": true,
 *         "push": false,
 *     }
 * }
 *
 * Updates Only Products and Settings to enable Pull and disable Push. Rest of the data types will remain unchanged.
 *
 *
 * POST wp-json/wc/gla/sync
 *
 *  {
 *      "products": {
 *           "pull": true,
 *      }
 *  }
 *
 * Updates Only Products to enable Pull. Rest of the data types and Products Pull method will remain unchanged.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI
 *
 * @since 3.0.0
 */
class SyncController extends BaseOptionsController {


	/**
	 * @var NotificationsService
	 */
	protected $notifications_service;

	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'sync';

	/**
	 * SyncController constructor.
	 *
	 * @param RESTServer           $server
	 * @param NotificationsService $notifications_service
	 */
	public function __construct( RESTServer $server, NotificationsService $notifications_service ) {
		parent::__construct( $server );
		$this->notifications_service = $notifications_service;
	}

	/**
	 * Registers the routes.
	 */
	public function register_routes() {
		$this->register_route(
			$this->route_base,
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_sync_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_update_sync_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_update_sync_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for getting the current sync mode for API PULL.
	 *
	 * @return callable
	 */
	protected function get_sync_callback(): callable {
		return function ( Request $request ) {
			try {
				$sync_mode = $this->notifications_service->get_current_sync_mode();
				return $this->prepare_item_for_response( $sync_mode, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for updating the current sync mode for API PULL.
	 *
	 * @return callable
	 */
	protected function get_update_sync_callback(): callable {
		return function ( Request $request ) {
			try {
				$sync_mode     = $this->notifications_service->get_current_sync_mode();
				$new_params    = $this->get_request_params( $request );
				$new_sync_mode = array_replace_recursive( $sync_mode, $new_params );

				$this->options->update( OptionsInterface::API_PULL_SYNC_MODE, $new_sync_mode );

				do_action( 'woocommerce_gla_sync_mode_updated', $sync_mode, $new_sync_mode );

				return $this->prepare_item_for_response( $this->options->get( OptionsInterface::API_PULL_SYNC_MODE ), $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return $this->route_base;
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'products' => [
				'type'        => 'object',
				'description' => 'Set pull and push sync method for Products.',
				'items'       => $this->get_pull_push_schema_fields(),
			],
			'coupons'  => [
				'type'        => 'object',
				'description' => 'Set the config for Push and Pull methods for Coupons.',
				'items'       => $this->get_pull_push_schema_fields(),
			],
			'shipping' => [
				'type'        => 'object',
				'description' => 'Set the config for Push and Pull methods for Shipping.',
				'items'       => $this->get_pull_push_schema_fields(),
			],
			'settings' => [
				'type'        => 'object',
				'description' => 'Set the config for Push and Pull methods for Settings.',
				'items'       => $this->get_pull_push_schema_fields(),
			],
		];
	}

	/**
	 * Get the item schema properties for the pull and push field.
	 *
	 * Push: Legacy method pushing store data to Google Merchant Center using Google's API
	 * Pull: New method where Google fetches the data from the store.
	 *
	 * If true: Method is enabled
	 * If false: Method is disabled
	 *
	 * @return array[]
	 */
	private function get_pull_push_schema_fields(): array {
		return [
			'push' => [
				'description' => 'Enable or disable Push method.',
				'type'        => 'boolean',
			],
			'pull' => [
				'description' => 'Enable or disable Pull method.',
				'type'        => 'boolean',
			],
		];
	}

	/**
	 * Get the query params for the update sync request.
	 *
	 * @return array
	 */
	protected function get_update_sync_params(): array {
		return $this->get_schema_properties();
	}

	/**
	 * Get the parameters from the request body.
	 * Only the keys in NotificationsService::get_default_sync_mode() that contains boolean pull and/or push param are allowed.
	 *
	 * @param Request $request The request
	 * @return array
	 */
	protected function get_request_params( Request $request ): array {
		$request_params = json_decode( $request->get_body(), true );

		if ( is_null( $request_params ) ) {
			return [];
		}

		$params       = array_intersect_key( $request_params, $this->notifications_service->get_default_sync_mode() );
		$valid_params = [];

		foreach ( $params as $key => $param ) {
			if ( isset( $param['push'] ) && is_bool( $param['push'] ) ) {
				$valid_params[ $key ]['push'] = $param['push'];
			}

			if ( isset( $param['pull'] ) && is_bool( $param['pull'] ) ) {
				$valid_params[ $key ]['pull'] = $param['pull'];
			}
		}

		return $valid_params;
	}
}
