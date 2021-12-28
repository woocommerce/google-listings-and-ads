<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BatchSchemaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateBatchController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateBatchController extends ShippingRateController {

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

		$this->register_route(
			"{$this->route_base}/suggestions/batch",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_batch_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'country_codes' => [
							'type'              => 'array',
							'description'       => __( 'Array of country codes in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
							'context'           => [ 'view' ],
							'sanitize_callback' => $this->get_country_code_sanitize_callback(),
							'validate_callback' => $this->get_country_code_validate_callback(),
							'minItems'          => 1,
							'required'          => true,
							'uniqueItems'       => true,
							'items'             => [
								'type' => 'string',
							],
						],
					],
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
			$currency      = $request->get_param( 'currency' );
			$rate          = $request->get_param( 'rate' );

			$responses = [];
			$errors    = [];
			foreach ( $country_codes as $country_code ) {
				$new_request = new Request( 'POST', "/{$this->get_namespace()}/{$this->route_base}" );
				$new_request->set_body_params(
					[
						'country_code' => $country_code,
						'currency'     => $currency,
						'rate'         => $rate,
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
	 * Get the callback for creating items via batch.
	 *
	 * @return callable
	 */
	protected function get_batch_suggestions_callback(): callable {
		return function( Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );

			$responses = [];
			$errors    = [];
			foreach ( $country_codes as $country_code ) {
				$new_request = new Request( 'GET', "/{$this->get_namespace()}/{$this->route_base}/suggestions" );
				$new_request->set_param( 'country_code', $country_code );

				$response = $this->server->dispatch_request( $new_request );
				if ( 200 !== $response->get_status() ) {
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
				200
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
		return 'batch_shipping_rates';
	}
}
