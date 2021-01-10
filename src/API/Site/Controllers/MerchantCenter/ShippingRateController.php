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
use Throwable;
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
				'schema' => $this->get_item_schema_callback(),
			]
		);

		$this->register_route(
			'mc/settings/shipping/batch',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_batch_create_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_batch_schema(),
				],
				'schema' => $this->get_batch_item_schema_callback(),
			]
		);

		$this->register_route(
			'mc/settings/shipping/(?P<country_code>\w+)',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_rate_schema(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_item_schema_callback(),
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
			foreach ( $rates as $country_code => $details ) {
				$items[ $country_code ] = $this->prepare_item_for_response( $details );
			}

			return $items;
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$country = $request->get_param( 'country_code' );
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
			$iso = $request->get_param( 'country_code' );
			$this->update_shipping_rates_option(
				$this->process_new_rate(
					$this->get_shipping_rates_option(),
					$iso,
					$request->get_params()
				)
			);

			return new WP_REST_Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully added rate for country.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * @return callable
	 */
	protected function get_delete_rate_callback(): callable {
		return function( WP_REST_Request $request ) {
			$iso   = $request->get_param( 'country_code' );
			$rates = $this->get_shipping_rates_option();
			unset( $rates[ $iso ] );
			$this->update_shipping_rates_option( $rates );

			return [
				'status'  => 'success',
				'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
					__( 'Successfully deleted the rate for country "%s".', 'google-listings-and-ads' ),
					$iso
				),
			];
		};
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

			$rates = $this->get_shipping_rates_option();
			foreach ( $country_codes as $country_code ) {
				$rates = $this->process_new_rate(
					$rates,
					$country_code,
					[
						'country_code' => $country_code,
						'currency'     => $currency,
						'rate'         => $rate,
					]
				);
			}

			$this->update_shipping_rates_option( $rates );

			return new WP_REST_Response(
				[
					'status'  => 'success',
					'message' => sprintf(
						/* translators: %s is a placeholder for comma-separated countries in ISO 3166-1 alpha-2 format. */
						__( 'Successfully added rate for countries: %s.', 'google-listings-and-ads' ),
						join( ',', $country_codes )
					),
				],
				201
			);
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
	 * Get the schema for batch shipping rates.
	 *
	 * @return array
	 */
	protected function get_batch_item_schema(): array {
		return $this->prepare_item_schema( $this->get_batch_schema(), 'batch_shipping_rates' );
	}

	/**
	 * Get the callback to obtain the item schema.
	 *
	 * @return callable
	 */
	protected function get_item_schema_callback(): callable {
		return function() {
			return $this->get_item_schema();
		};
	}

	/**
	 * Get the callback to obtain the batch item schema.
	 *
	 * @return callable
	 */
	protected function get_batch_item_schema_callback(): callable {
		return function() {
			return $this->get_batch_item_schema();
		};
	}

	/**
	 * @return array
	 */
	protected function get_rate_schema(): array {
		return [
			'country'      => [
				'type'        => 'string',
				'description' => __( 'Country in which the shipping rate applies.', 'google-listings-and-ads' ),
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
			'currency'     => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD', // todo: default to store currency.
			],
			'rate'         => [
				'type'              => 'integer',
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
		];
	}

	/**
	 * Get the schema for a batch request.
	 *
	 * @return array
	 */
	protected function get_batch_schema(): array {
		$schema = $this->get_rate_schema();
		unset( $schema['country'], $schema['country_code'] );

		// Context is always edit for batches.
		foreach ( $schema as $key => &$value ) {
			$value['context'] = [ 'edit' ];
		}

		$schema['country_codes'] = [
			'type'              => 'array',
			'description'       => __( 'Array of country codes in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
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
	 * Get the array of shipping rates from the option store.
	 *
	 * @return array
	 */
	protected function get_shipping_rates_option(): array {
		return $this->options->get( OptionsInterface::SHIPPING_RATES, [] );
	}

	/**
	 * Update the array of shipping rates in the options store.
	 *
	 * @param array $rates
	 *
	 * @return bool
	 */
	protected function update_shipping_rates_option( array $rates ): bool {
		return $this->options->update( OptionsInterface::SHIPPING_RATES, $rates );
	}

	/**
	 * Validate that a country is valid.
	 *
	 * @param string $country The alpha2 country code.
	 *
	 * @throws OutOfBoundsException When the country code cannot be found.
	 */
	protected function validate_country_code( string $country ): void {
		$this->iso->alpha2( $country );
	}

	/**
	 * Get the callback to sanitize the country code.
	 *
	 * Necessary because strtoupper() will trigger warnings when extra parameters are passed to it.
	 *
	 * @return callable
	 */
	protected function get_country_code_sanitize_callback(): callable {
		return function( $value ) {
			return is_array( $value )
				? array_map( 'strtoupper', $value )
				: strtoupper( $value );
		};
	}

	/**
	 * Get a callable function for validating that a provided country code is recognized.
	 *
	 * @return callable
	 */
	protected function get_country_code_validate_callback(): callable {
		return function( $value ) {
			try {
				// This is used for individual strings and an array of strings.
				$value = (array) $value;
				foreach ( $value as $item ) {
					$this->validate_country_code( $item );
				}

				return true;
			} catch ( Throwable $e ) {
				return $this->error_from_exception(
					$e,
					'gla_invalid_country',
					[
						'status'  => 400,
						'country' => $item,
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

	/**
	 * Process a new rate and add it to the other rates.
	 *
	 * @param array  $all_rates Array of all rates.
	 * @param string $rate_key  The rate key.
	 * @param array  $raw_data  Raw data to process.
	 *
	 * @return array
	 */
	protected function process_new_rate( array $all_rates, string $rate_key, array $raw_data ): array {
		$schema = $this->get_rate_schema();
		$rate   = $all_rates[ $rate_key ] ?? [];
		foreach ( $schema as $key => $property ) {
			$rate[ $key ] = $raw_data[ $key ] ?? $rate[ $key ] ?? $property['default'] ?? null;
		}

		// todo: translate the country using WC_Countries class
		$rate['country']        = $this->iso->alpha2( $rate_key )['name'];
		$all_rates[ $rate_key ] = $rate;

		return $all_rates;
	}
}
