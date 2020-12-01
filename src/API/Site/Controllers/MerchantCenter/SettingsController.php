<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SettingsController extends BaseController {

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/settings',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_settings_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_settings_endpoint_edit_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_item_schema(),
			]
		);
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


	protected function get_settings_endpoint_edit_callback(): callable {
		return function( WP_REST_Request $request ) {
			// todo: replace this placeholder with real logic.
			return [];
		};
	}


	public function get_item_schema(): array {
		$properties = $this->get_settings_schema();
		array_walk(
			$properties,
			function( &$value ) {
				unset(
					$value['items'],
					$value['validate_callback'],
					$value['sanitize_callback']
				);
			}
		);

		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'merchant_center_settings',
			'type'       => 'object',
			'properties' => $properties,
		];
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
