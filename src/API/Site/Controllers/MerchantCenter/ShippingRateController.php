<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateController extends BaseOptionsController {

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/settings/shipping',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rates_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_item_schema(),
			]
		);

		$this->register_route(
			'mc/settings/shipping/(?P<country>[\w-]+)',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for returning the endpoint results.
	 *
	 * @return callable
	 */
	protected function get_read_rates_callback(): callable {
		return function() {
			$rates = $this->get_shipping_rates_option();
			$items = [];
			foreach ( $rates as $country => $details ) {
				$items[ $country ] = $this->prepare_item_for_response( $details );
			}

			return $items;
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country = $request->get_param( 'country' );
			$rates   = $this->get_shipping_rates_option();
			if ( ! array_key_exists( $country, $rates ) ) {
				return new WP_REST_Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return $this->prepare_item_for_response( $rates[ $country ] );
		};
	}

	/**
	 * Get the callback function for creating a new shipping rate.
	 *
	 * @return callable
	 */
	protected function get_create_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$schema  = $this->get_rate_schema();
			$rates   = $this->get_shipping_rates_option();
			$country = $this->slugify_country( $request->get_param( 'country' ) );
			$rate    = $rates[ $country ] ?? [];
			foreach ( $schema as $key => $property ) {
				$rate[ $key ] = $request->get_param( $key ) ?? $rate[ $key ] ?? $property['default'] ?? null;
			}

			$rates[ $country ] = $rate;
			$this->options->update( OptionsInterface::SHIPPING_RATES, $rates );

			return [
				'status'  => 'success',
				'message' => __( 'Successfully added rate for country.', 'google-listings-and-ads' ),
			];
		};
	}

	/**
	 * Prepare an item to be returned as a response.
	 *
	 * @param array $item_data The raw item data.
	 *
	 * @return array
	 */
	protected function prepare_item_for_response( array $item_data ): array {
		$prepared = [];
		$schema   = $this->get_rate_schema();
		foreach ( $schema as $key => $property ) {
			$prepared[ $key ] = $item_data[ $key ] ?? $property['default'] ?? null;
		}

		return $prepared;
	}

	/**
	 * Get the schema for shipping rates.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		return $this->prepare_item_schema( $this->get_rate_schema(), 'shipping_rates' );
	}

	/**
	 * @return array
	 */
	protected function get_rate_schema(): array {
		return [
			// todo: consider including an enum of valid countries.
			'country'  => [
				'type'              => 'string',
				'description'       => __( 'Country in which the shipping rate applies.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'currency' => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD',
			],
			'rate'     => [
				'type'              => 'integer',
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the array of shipping rates from the option store.
	 *
	 * @return array
	 */
	protected function get_shipping_rates_option(): array {
		return $this->options->get( OptionsInterface::SHIPPING_RATES, [] );
	}

	/**
	 * @param string $country
	 *
	 * @return string
	 */
	protected function slugify_country( string $country ): string {
		return strtolower( str_replace( [ '_', ' ' ], '-', $country ) );
	}
}
