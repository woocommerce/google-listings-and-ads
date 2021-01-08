<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\Exception\OutOfBoundsException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use WP_Error;
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
	 * @var ISO3166DataProvider
	 */
	protected $iso;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer          $server
	 * @param OptionsInterface    $options
	 * @param ISO3166DataProvider $iso
	 */
	public function __construct( RESTServer $server, OptionsInterface $options, ISO3166DataProvider $iso ) {
		parent::__construct( $server, $options );
		$this->iso = $iso;
	}

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
					'args'                => $this->get_rate_schema(),
				],
				'schema' => $this->get_item_schema(),
			]
		);

		$this->register_route(
			'mc/settings/shipping/(?P<iso_code>\w+)',
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
			foreach ( $rates as $iso => $details ) {
				$items[ $iso ] = $this->prepare_item_for_response( $details );
			}

			return $items;
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country = $request->get_param( 'iso_code' );
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
			$country = $request->get_param( 'country' );
			$schema  = $this->get_rate_schema();
			$rates   = $this->get_shipping_rates_option();

			try {
				$iso  = $this->get_country_iso_code( $country );
				$rate = $rates[ $iso ] ?? [];
				foreach ( $schema as $key => $property ) {
					$rate[ $key ] = $request->get_param( $key ) ?? $rate[ $key ] ?? $property['default'] ?? null;
				}

				$rates[ $iso ] = $rate;
				$this->options->update( OptionsInterface::SHIPPING_RATES, $rates );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully added rate for country.', 'google-listings-and-ads' ),
				];
			} catch ( OutOfBoundsException $e ) {
				return $this->error_from_exception(
					$e,
					'gla_invalid_country',
					[
						'status'  => 400,
						'country' => $country,
					]
				);
			}
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
			'country'  => [
				'type'              => 'string',
				'description'       => __( 'Country in which the shipping rate applies.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => $this->get_country_validate_callback(),
				'required'          => true,
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
				'required'          => true,
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
	protected function get_country_iso_code( string $country ): string {
		$data = $this->get_country_data( $country );

		return $data['alpha2'];
	}

	/**
	 * @param string $country_name
	 *
	 * @return array
	 * @throws OutOfBoundsException When the country name cannot be found.
	 */
	protected function get_country_data( string $country_name ): array {
		return $this->iso->name( $country_name );
	}

	/**
	 * Validate that a country is valid.
	 *
	 * @param string $country The country name.
	 *
	 * @throws OutOfBoundsException When the country name cannot be found.
	 */
	protected function validate_country( string $country ): void {
		$this->iso->name( $country );
	}

	/**
	 * Get a callable function for validating that a provided country is recognized.
	 *
	 * @return callable
	 */
	protected function get_country_validate_callback(): callable {
		return function( $value ) {
			try {
				$this->validate_country( $value );

				return true;
			} catch ( Exception $e ) {
				return $this->error_from_exception(
					$e,
					'gla_invalid_country',
					[
						'status'  => 400,
						'country' => $value,
					]
				);
			}
		};
	}

	/**
	 * Create a WP_Error from an exception.
	 *
	 * @param Exception $e
	 * @param string    $code
	 * @param array     $data
	 *
	 * @return WP_Error
	 */
	protected function error_from_exception( Exception $e, string $code, array $data = [] ): WP_Error {
		return new WP_Error( $code, $e->getMessage(), $data );
	}
}
