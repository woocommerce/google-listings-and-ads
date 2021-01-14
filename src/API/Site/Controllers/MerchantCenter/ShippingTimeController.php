<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeController extends BaseOptionsController {

	use ControllerTrait;
	use CountryCodeTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'mc/shipping/times',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_times_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_time_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'mc/shipping/times/(?P<country_code>\w+)',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_time_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_time_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for reading times.
	 *
	 * @return callable
	 */
	protected function get_read_times_callback(): callable {
		return function() {
			$times = $this->get_shipping_times_option();
			$items = [];
			foreach ( $times as $country_code => $details ) {
				$items[ $country_code ] = $this->prepare_item_for_response( $details );
			}

			return $items;
		};
	}

	/**
	 * Get the callback function for reading a single time.
	 *
	 * @return callable
	 */
	protected function get_read_time_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country = $request->get_param( 'country_code' );
			$times   = $this->get_shipping_times_option();
			if ( ! array_key_exists( $country, $times ) ) {
				return new WP_REST_Response(
					[
						'message' => __( 'No time available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return $this->prepare_item_for_response( $times[ $country ] );
		};
	}

	/**
	 * Get the callback to crate a new time.
	 *
	 * @return callable
	 */
	protected function get_create_time_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country_code           = $request->get_param( 'country_code' );
			$times                  = $this->get_shipping_times_option();
			$times[ $country_code ] = $this->process_new_time(
				$times[ $country_code ] ?? [],
				$request->get_params(),
				$country_code
			);

			$this->update_shipping_times_option( $times );

			return new WP_REST_Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully added time for country.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * Get the callback function for deleting a time.
	 *
	 * @return callable
	 */
	protected function get_delete_time_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country_code = $request->get_param( 'country_code' );
			$times        = $this->get_shipping_times_option();

			unset( $times[ $country_code ] );
			$this->update_shipping_times_option( $times );

			return [
				'status'  => 'success',
				'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
					__( 'Successfully deleted the time for country "%s".', 'google-listings-and-ads' ),
					$country_code
				),
			];
		};
	}

	/**
	 * Process new data for a time option.
	 *
	 * @param array  $existing     Existing time data.
	 * @param array  $new          New time data.
	 * @param string $country_code The country code for the time.
	 *
	 * @return array
	 */
	protected function process_new_time( array $existing, array $new, string $country_code ): array {
		$schema = $this->get_item_schema();
		$time   = [];
		foreach ( $schema as $key => $property ) {
			$time[ $key ] = 'country' === $key
				? $this->iso->alpha2( $country_code )['name']
				: $new[ $key ] ?? $existing[ $key ] ?? $property['default'] ?? null;
		}

		return $time;
	}

	/**
	 * Get the shipping times option from the options object.
	 *
	 * @return array
	 */
	protected function get_shipping_times_option(): array {
		return $this->options->get( OptionsInterface::SHIPPING_TIMES, [] );
	}

	/**
	 * Update the array of shipping times in the options object.
	 *
	 * @param array $times
	 *
	 * @return bool
	 */
	protected function update_shipping_times_option( array $times ): bool {
		return $this->options->update( OptionsInterface::SHIPPING_TIMES, $times );
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		return [
			'country'      => [
				'type'        => 'string',
				'description' => __( 'Country in which the shipping time applies.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'country_code' => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
			],
			'time'         => [
				'type'              => 'integer',
				'description'       => __( 'The shipping time in days.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
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
	protected function get_item_schema_name(): string {
		return 'shipping_times';
	}
}
