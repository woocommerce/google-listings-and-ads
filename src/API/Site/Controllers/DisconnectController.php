<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class DisconnectController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
class DisconnectController extends BaseController {

	use EmptySchemaPropertiesTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes() {
		$this->register_route(
			'connections',
			[
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_disconnect_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback for disconnecting all the services.
	 *
	 * @return callable
	 */
	protected function get_disconnect_callback(): callable {
		return function ( Request $request ) {
			$endpoints = [
				'ads/connection',
				'mc/connection',
				'google/connect',
				'jetpack/connect',
				'rest-api/authorize',
			];

			$errors    = [];
			$responses = [];
			foreach ( $endpoints as $endpoint ) {
				$response = $this->get_delete_response( $endpoint );
				if ( 200 !== $response->get_status() ) {
					$errors[ $response->get_matched_route() ] = $response->get_data();
				} else {
					$responses[ $response->get_matched_route() ] = $response->get_data();
				}
			}

			return new Response(
				[
					'errors'    => $errors,
					'responses' => $responses,
				],
				empty( $errors ) ? 200 : 400
			);
		};
	}

	/**
	 * Run a DELETE request for a given path, and return the response.
	 *
	 * @param string $path The relative API path. Based on the shared namespace.
	 *
	 * @return Response
	 */
	protected function get_delete_response( string $path ): Response {
		$path = ltrim( $path, '/' );

		return $this->server->dispatch_request( new Request( 'DELETE', "/{$this->get_namespace()}/{$path}" ) );
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'disconnect_all_accounts';
	}
}
