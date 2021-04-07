<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BatchSchemaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeBatchController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeBatchController extends ShippingTimeController {

	use BatchSchemaTrait;
	use BatchShippingTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			"{$this->route_base}/batch",
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_batch_create_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_batch_delete_shipping_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_delete_schema(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback for creating items via batch.
	 *
	 * @return callable
	 */
	protected function get_batch_create_callback(): callable {
		return function( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );
			$time          = $request->get_param( 'time' );

			$responses = [];
			$errors    = [];
			foreach ( $country_codes as $country_code ) {
				$new_request = new Request( 'POST', "/{$this->get_namespace()}/{$this->route_base}" );
				$new_request->set_body_params(
					[
						'country_code' => $country_code,
						'time'         => $time,
					]
				);

				$response = $this->server->dispatch_request( $new_request );
				if ( 201 !== $response->get_status() ) {
					$errors[] = $response->get_data();
				} else {
					$responses[] = $response->get_data();
				}
			}

			return new Response(
				[
					'errors'  => $errors,
					'success' => $responses,
				],
				201
			);
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
		return 'batch_shipping_times';
	}
}
