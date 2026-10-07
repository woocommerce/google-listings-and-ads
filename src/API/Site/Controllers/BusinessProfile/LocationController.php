<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationController
 *
 * Lists the merchant's Business Profile locations and connects one of them.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BusinessProfile
 */
class LocationController extends BaseController {

	/** @var LocationService */
	protected $location_service;

	/**
	 * LocationController constructor.
	 *
	 * @param RESTServer      $server
	 * @param LocationService $location_service
	 */
	public function __construct( RESTServer $server, LocationService $location_service ) {
		parent::__construct( $server );

		$this->location_service = $location_service;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'google-business-profile/locations',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_locations_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_select_location_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for listing the merchant's locations.
	 *
	 * @return callable
	 */
	protected function get_locations_callback(): callable {
		return function () {
			try {
				return $this->location_service->list_locations();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for connecting a location.
	 *
	 * @return callable
	 */
	protected function get_select_location_callback(): callable {
		return function ( Request $request ) {
			try {
				$this->location_service->select_location( sanitize_text_field( (string) $request['id'] ) );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully connected Google Business Profile location.', 'google-listings-and-ads' ),
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
				'description' => __( 'The Google Business Profile location ID to connect.', 'google-listings-and-ads' ),
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
		return 'google_business_profile_location';
	}
}
