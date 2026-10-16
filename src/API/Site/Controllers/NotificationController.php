<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for retrieving and dismissing notifications.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class NotificationController extends BaseController {

	/**
	 * Service used to retrieve and dismiss notifications.
	 *
	 * @var NotificationService
	 */
	protected $service;

	/**
	 * NotificationController constructor.
	 *
	 * @param RESTServer          $server
	 * @param NotificationService $service
	 */
	public function __construct( RESTServer $server, NotificationService $service ) {
		parent::__construct( $server );
		$this->service = $service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'notifications',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		$this->register_route(
			"notifications/(?P<id>{$this->get_notification_id_regex()})",
			[
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [ 'id' => $this->get_notification_item_properties()['id'] ],
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Callback function for returning the active notifications.
	 *
	 * @return callable
	 */
	protected function get_read_callback(): callable {
		return function () {
			try {
				return new Response( $this->get_notifications_response() );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Callback function for dismissing a notification.
	 *
	 * @return callable
	 */
	protected function get_delete_callback(): callable {
		return function ( Request $request ) {
			try {
				$id = (string) $request->get_param( 'id' );

				if ( ! $this->service->has( $id ) ) {
					return new Response(
						[
							'message' => __( 'No notification found with the given ID.', 'google-listings-and-ads' ),
							'id'      => $id,
						],
						404
					);
				}

				$this->service->dismiss( $id );

				return new Response( $this->get_notifications_response() );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Build the notifications response payload.
	 *
	 * @return array
	 */
	protected function get_notifications_response(): array {
		return [
			'notifications' => $this->service->get_notifications(),
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array The Schema properties
	 */
	protected function get_schema_properties(): array {
		return [
			'notifications' => [
				'type'        => 'array',
				'description' => __( 'Active notifications.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => $this->get_notification_item_properties(),
				],
			],
		];
	}

	/**
	 * Get the schema properties for a single notification item.
	 *
	 * @return array
	 */
	protected function get_notification_item_properties(): array {
		return [
			'id'           => [
				'description'       => __( 'The notification ID.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'pattern'           => "^{$this->get_notification_id_regex()}$",
				'context'           => [ 'view' ],
				'readonly'          => true,
			],
			'triggered_at' => [
				'description' => __( 'The timestamp when the notification was triggered.', 'google-listings-and-ads' ),
				'type'        => 'integer',
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
		return 'notifications';
	}

	/**
	 * Get the regex used for the notification ID.
	 *
	 * @return string The regex
	 */
	protected function get_notification_id_regex(): string {
		return '[a-zA-Z0-9_-]+';
	}
}
