<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding;

use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
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

	use PermissionsTrait;

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

		$this->register_route(
			'mc/settings',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_settings_endpoint_read_callback(),
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

	/**
	 * Get a callback for the settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_read_callback(): callable {
		return function( WP_REST_Request $request ) {
			// todo: replace this placeholder with real logic.
			return [];
		};
	}

	/**
	 * Get a callback for the update settings endpoint.
	 *
	 * @return callable
	 */
	protected function get_settings_endpoint_update_callback(): callable {
		return function( WP_REST_Request $request ) {
			// todo: replace this placeholder with real logic.
			return [];
		};
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_settings_schema(): array {
		return [
			// todo: consider including an enum of valid countries.
			'countries'               => [
				'type'              => 'array',
				'description'       => __( 'Countries in which products are available.', '' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type' => 'string',
				],
			],
			'shipping'                => [
				'type'              => 'string',
				'description'       => __(
					'Whether shipping is set up automatically by the plugin or manually in the Merchant Center.',
					''
				),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [
					'automatic',
					'manual',
				],
			],
			'estimated_shipping_rate' => [
				'type'              => 'string',
				'description'       => __( 'Estimated flat shipping rate (USD)', '' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'estimated_shipping_days' => [
				'type'              => 'integer',
				'description'       => __( 'Estimated shipping time (in days).', '' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}
