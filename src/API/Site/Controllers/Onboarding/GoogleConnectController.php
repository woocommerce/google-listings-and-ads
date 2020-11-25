<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class GoogleConnectController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding
 */
class GoogleConnectController extends BaseController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'google/connected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => function( WP_REST_Request $request ) {
						return $this->google_connect( $request );
					},
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => [],
			]
		);
	}

	/**
	 * Return the google connect response.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	protected function google_connect( WP_REST_Request $request ) {
		return rest_ensure_response( [ 'success' => true ] );
	}
}
