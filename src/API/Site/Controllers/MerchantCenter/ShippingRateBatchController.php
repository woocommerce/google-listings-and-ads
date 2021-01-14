<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateBatchController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateBatchController extends ShippingRateController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			"{$this->route_base}/batch",
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_batch_create_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
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
		return function( WP_REST_Request $request ) {
			$country_codes = $request->get_param( 'country_codes' );
			$currency      = $request->get_param( 'currency' );
			$rate          = $request->get_param( 'rate' );

			$responses = [];
			$errors    = [];
			foreach ( $country_codes as $country_code ) {
				$new_request = new WP_REST_Request( 'POST', "/{$this->get_namespace()}/{$this->route_base}" );
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

			return new WP_REST_Response(
				[
					'errors'  => $errors,
					'success' => $responses,
				],
				201
			);
		};
	}

	/**
	 * Get the schema for a batch request.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		$schema = parent::get_item_schema();
		unset( $schema['country'], $schema['country_code'] );

		// Context is always edit for batches.
		foreach ( $schema as $key => &$value ) {
			$value['context'] = [ 'edit' ];
		}

		$schema['country_codes'] = [
			'type'              => 'array',
			'description'       => __(
				'Array of country codes in ISO 3166-1 alpha-2 format.',
				'google-listings-and-ads'
			),
			'context'           => [ 'edit' ],
			'sanitize_callback' => $this->get_country_code_sanitize_callback(),
			'validate_callback' => $this->get_country_code_validate_callback(),
			'minItems'          => 1,
			'required'          => true,
			'uniqueItems'       => true,
			'items'             => [
				'type' => 'string',
			],
		];

		return $schema;
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_item_schema_name(): string {
		return 'batch_shipping_rates';
	}
}
