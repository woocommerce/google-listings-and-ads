<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding
 */
class MerchantCenterController extends BaseController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/connected',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_connected_endpoint_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get a callback for the connected endpoint.
	 *
	 * @return callable
	 */
	protected function get_connected_endpoint_callback(): callable {
		return function() {
			// todo: replace this placeholder with real logic.
			return [
				'connected' => 'no logic',
			];
		};
	}
}
