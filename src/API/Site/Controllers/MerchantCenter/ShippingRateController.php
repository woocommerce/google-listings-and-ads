<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateController extends BaseController implements ISO3166AwareInterface {

	use CountryCodeTrait;


	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'mc/shipping/rates';

	/**
	 * @var ShippingRateQuery
	 */
	protected $query;

	/**
	 * @var ShippingZone
	 */
	protected $shipping_zone;

	/**
	 * ShippingRateController constructor.
	 *
	 * @param RESTServer        $server
	 * @param ShippingRateQuery $query
	 * @param ShippingZone      $shipping_zone
	 */
	public function __construct( RESTServer $server, ShippingRateQuery $query, ShippingZone $shipping_zone ) {
		parent::__construct( $server );
		$this->query         = $query;
		$this->shipping_zone = $shipping_zone;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			$this->route_base,
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_all_rates_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			"{$this->route_base}/(?P<id>[\d]+)",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [ 'id' => $this->get_schema_properties()['id'] ],
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->get_update_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [ 'id' => $this->get_schema_properties()['id'] ],
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			"{$this->route_base}/suggestions",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_suggestions_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [ 'country' => $this->get_schema_properties()['country'] ],
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for returning the endpoint results.
	 *
	 * @return callable
	 */
	protected function get_read_all_rates_callback(): callable {
		return function ( Request $request ) {
			$rates = $this->get_all_shipping_rates();

			return array_map(
				function ( $rate ) use ( $request ) {
					$response = $this->prepare_item_for_response( $rate, $request );

					return $this->prepare_response_for_collection( $response );
				},
				$rates
			);
		};
	}

	/**
	 * Get the callback function for returning the endpoint results.
	 *
	 * @return callable
	 *
	 * @since x.x.x
	 */
	protected function get_suggestions_callback(): callable {
		return function ( Request $request ) {
			$country = $request->get_param( 'country' );
			$rates   = $this->get_suggested_shipping_rates_for_country( $country );
			if ( empty( $rates ) ) {
				return new Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return array_map(
				function ( $rate ) use ( $request ) {
					$response = $this->prepare_item_for_response( $rate, $request );

					// Suggestions don't have a id.
					$data = $response->get_data();
					unset( $data['id'] );
					$response->set_data( $data );

					return $this->prepare_response_for_collection( $response );
				},
				$rates
			);
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function ( Request $request ) {
			$id   = (string) $request->get_param( 'id' );
			$rate = $this->get_shipping_rate_by_id( $id );
			if ( empty( $rate ) ) {
				return new Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'id'      => $id,
					],
					404
				);
			}

			return $this->prepare_item_for_response( $rate, $request );
		};
	}

	/**
	 * @return callable
	 *
	 * @since x.x.x
	 */
	protected function get_update_rate_callback(): callable {
		return function ( Request $request ) {
			$id = (string) $request->get_param( 'id' );

			$rate = $this->get_shipping_rate_by_id( $id );
			if ( empty( $rate ) ) {
				return new Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'id'      => $id,
					],
					404
				);
			}

			$data = $this->prepare_item_for_database( $request );
			$this->create_query()->update(
				$data,
				[
					'id' => $id,
				]
			);

			return new Response( '', 204 );
		};
	}

	/**
	 * Get the callback function for creating a new shipping rate.
	 *
	 * @return callable
	 */
	protected function get_create_rate_callback(): callable {
		return function ( Request $request ) {
			$update_query = $this->create_query();

			try {
				$data    = $this->prepare_item_for_database( $request );
				$country = $data['country'];
				$method  = $data['method'];

				$existing_query = $this->create_query()->where( 'country', $country )->where( 'method', $method );
				$existing       = ! empty( $existing_query->get_results() );

				if ( $existing ) {
					$update_query->update(
						$data,
						[
							'id' => $existing_query->get_results()[0]['id'],
						]
					);
				} else {
					$update_query->insert( $data );
				}
			} catch ( InvalidQuery $e ) {
				return $this->error_from_exception(
					$e,
					'gla_error_creating_shipping_rate',
					[
						'code'    => 400,
						'message' => $e->getMessage(),
					]
				);
			}

			return new Response(
				[
					'status'  => 'success',
					'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'Successfully added rates for country: "%s".', 'google-listings-and-ads' ),
						$country
					),
				],
				201
			);
		};
	}

	/**
	 * @return callable
	 */
	protected function get_delete_rate_callback(): callable {
		return function( Request $request ) {
			try {
				$id = (string) $request->get_param( 'id' );
				$this->create_query()->delete( 'id', $id );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully deleted rate.', 'google-listings-and-ads' ),
				];
			} catch ( InvalidQuery $e ) {
				return $this->error_from_exception(
					$e,
					'gla_error_deleting_shipping_rate',
					[
						'code'    => 400,
						'message' => $e->getMessage(),
					]
				);
			}
		};
	}

	/**
	 * Returns the list of all shipping rates stored in the database grouped by their respective country code.
	 *
	 * @return array Array of shipping rates grouped by country code.
	 */
	protected function get_all_shipping_rates(): array {
		return $this->create_query()
					->set_limit( 200 )
					->set_order( 'country', 'ASC' )
					->get_results();
	}

	/**
	 * @param string $id
	 *
	 * @return array|null The shipping rate properties as an array or null if it doesn't exist.
	 */
	protected function get_shipping_rate_by_id( string $id ): ?array {
		$results = $this->create_query()->where( 'id', $id )->get_results();

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Return a new instance of the shipping rate query object.
	 *
	 * @return ShippingRateQuery
	 */
	protected function create_query(): ShippingRateQuery {
		return clone $this->query;
	}

	/**
	 * @param string $country
	 *
	 * @return array|null
	 *
	 * @since x.x.x
	 */
	protected function get_suggested_shipping_rates_for_country( string $country ): ?array {
		return $this->shipping_zone->get_shipping_rates_for_country( $country );
	}

	/**
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'       => [
				'type'        => 'number',
				'description' => __( 'The shipping rate unique identification number.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'country'  => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
			],
			'method'   => [
				'type'              => 'string',
				'description'       => __( 'The shipping method.', 'google-listings-and-ads' ),
				'enum'              => [
					ShippingZone::METHOD_FLAT_RATE,
				],
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'currency' => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD', // todo: default to store currency.
			],
			'rate'     => [
				'type'              => 'number',
				'minimum'           => 0,
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'options'  => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Array of options for the shipping method.', 'google-listings-and-ads' ),
				'context'              => [ 'view', 'edit' ],
				'validate_callback'    => 'rest_validate_request_arg',
				'default'              => [],
				'properties'           => [
					'free_shipping_threshold' => [
						'type'              => 'number',
						'minimum'           => 0,
						'description'       => __( 'Minimum price eligible for free shipping.', 'google-listings-and-ads' ),
						'context'           => [ 'view', 'edit' ],
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
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
	protected function get_schema_title(): string {
		return 'shipping_rates';
	}
}
