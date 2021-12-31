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
use WP_Error;
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
					'callback'            => $this->get_read_rates_callback(),
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
			"{$this->route_base}/(?P<country_code>\\w{2})",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->get_delete_rate_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
					'args'                => [
						'country_code' => [
							'type'              => 'string',
							'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
							'context'           => [ 'view' ],
							'sanitize_callback' => $this->get_country_code_sanitize_callback(),
							'validate_callback' => $this->get_country_code_validate_callback(),
							'required'          => true,
						],
					],
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
	protected function get_read_rates_callback(): callable {
		return function ( Request $request ) {
			$rates = $this->get_all_shipping_rates();
			$items = [];
			foreach ( $rates as $country => $country_rates ) {
				$data = $this->prepare_item_for_response(
					[
						'country_code' => $country,
						'rates'        => $country_rates,
					],
					$request
				);

				$items[ $country ] = $this->prepare_response_for_collection( $data );
			}

			return $items;
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
			$country = $request->get_param( 'country_code' );
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

			return $this->prepare_item_for_response( $rates, $request );
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function ( Request $request ) {
			$country = $request->get_param( 'country_code' );
			$rates   = $this->get_shipping_rates_for_country( $country );
			if ( empty( $rates ) ) {
				return new Response(
					[
						'message' => __( 'No rate available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return $this->prepare_item_for_response(
				[
					'country_code' => $country,
					'rates'        => array_values( $rates ),
				],
				$request
			);
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
			$country_code = $request->get_param( 'country_code' );

			// First we delete all rates for this country (to avoid duplicates).
			$this->create_query()->delete( 'country', $country_code );

			$rates = $request->get_param( 'rates' );
			if ( empty( $rates ) ) {
				return new WP_Error(
					400,
					sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'No rates provided for country: "%s".', 'google-listings-and-ads' ),
						$country_code
					)
				);
			}

			foreach ( $rates as ['method' => $method, 'currency' => $currency, 'rate' => $rate, 'options' => $options] ) {
				try {
					$data = [
						'country'  => $country_code,
						'method'   => $method,
						'currency' => $currency,
						'rate'     => $rate,
						'options'  => $options,
					];

					$update_query->insert( $data );
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
			}

			return new Response(
				[
					'status'  => 'success',
					'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'Successfully added rates for country: "%s".', 'google-listings-and-ads' ),
						$country_code
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
				$country_code = $request->get_param( 'country_code' );
				$this->create_query()->delete( 'country', $country_code );

				return [
					'status'  => 'success',
					'message' => sprintf(
						/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'Successfully deleted the rate for country: "%s".', 'google-listings-and-ads' ),
						$country_code
					),
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
		$results = $this->create_query()->set_limit( 100 )->get_results();

		return $this->group_rates_by_country( $results );
	}

	/**
	 * @param string $country
	 *
	 * @return array
	 */
	protected function get_shipping_rates_for_country( string $country ): array {
		$results = $this->create_query()->where( 'country', $country )->get_results();

		return $this->group_rates_by_country( $results );
	}

	/**
	 * @param array $rates
	 *
	 * @return array
	 *
	 * @since x.x.x
	 */
	protected function group_rates_by_country( array $rates ): array {
		$rates_grouped = [];
		foreach ( $rates as ['country' => $country, 'method' => $method, 'currency' => $currency, 'rate' => $rate, 'options' => $options] ) {
			// Initialize the country rates array.
			$rates_grouped[ $country ] = $rates_grouped[ $country ] ?? [];

			$rates_grouped[ $country ][] = [
				'method'   => $method,
				'currency' => $currency,
				'rate'     => $rate,
				'options'  => $options,
			];
		}

		return $rates_grouped;
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
		$rates = [];

		// Todo: Render the cost of shipping classes for the flat-shipping rate.

		$methods       = $this->shipping_zone->get_shipping_methods_for_country( $country );
		$free_shipping = $this->find_free_shipping_method( $methods );
		foreach ( $methods as $method ) {
			// We process the free shipping method separately.
			if ( ! $method['enabled'] || ShippingZone::METHOD_FREE === $method['id'] ) {
				continue;
			}

			// We can skip the pickup method because it's still not supported.
			// Todo: Add support for the pickup method once it's available.
			if ( ShippingZone::METHOD_PICKUP === $method['id'] ) {
				continue;
			}

			$rate = [
				'method'   => $method['id'],
				'currency' => $method['currency'],
				'rate'     => $method['options']['cost'] ?? 0,
				'options'  => [],
			];

			if ( null !== $free_shipping ) {
				if ( isset( $free_shipping['options']['min_amount'] ) ) {
					// If there is a free shipping method, and it has a minimum order amount, we set it as an option for all rates.
					$rate['options']['free_shipping_threshold'] = $free_shipping['options']['min_amount'];
				} else {
					// If there is a free shipping method without a minimum order amount, we set the rate to 0 to mark it as free.
					$rate['rate'] = 0;
				}
			}

			if ( ! empty( $method['options']['class_costs'] ) ) {
				// If there are shipping classes, we set the cost of each class as an option.
				$rate['options']['shipping_class_rates'] = [];
				foreach ( $method['options']['class_costs'] as $class_id => $cost ) {
					$rate['options']['shipping_class_rates'][] = [
						'class' => $class_id,
						'rate'  => $cost,
					];
				}
			}

			$rates[] = $rate;
		}

		return [
			'country_code' => $country,
			'rates'        => $rates,
		];
	}

	/**
	 * Finds and returns the free shipping method if it exists in the list of suggested shipping methods.
	 *
	 * @param array $methods
	 *
	 * @return array|null Array containing the free shipping method properties, or null if it does not exist.
	 *
	 * @since x.x.x
	 */
	protected function find_free_shipping_method( array $methods ): ?array {
		$free_shipping_method = array_filter(
			$methods,
			function ( $method ) {
				return ShippingZone::METHOD_FREE === $method['id'];
			}
		);

		if ( empty( $free_shipping_method ) ) {
			return null;
		}

		return array_values( $free_shipping_method )[0];
	}

	/**
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'country_code' => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
			],
			'rates'        => [
				'type'              => 'array',
				'minItems'          => 1,
				'uniqueItems'       => true,
				'description'       => __( 'Array of shipping rates for the given country_code.', 'google-listings-and-ads' ),
				'validate_callback' => 'rest_validate_request_arg',
				'items'             => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'method'   => [
							'type'              => 'string',
							'description'       => __( 'The shipping method.', 'google-listings-and-ads' ),
							'enum'              => [
								ShippingZone::METHOD_FLAT_RATE,
								// ShippingZone::METHOD_PICKUP, // Todo: Add support for the pickup method once it's available.
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
								'shipping_class_rates'    => [
									'type'              => 'array',
									'description'       => __( 'An array of rates for shipping classes/labels.', 'google-listings-and-ads' ),
									'context'           => [ 'view', 'edit' ],
									'validate_callback' => 'rest_validate_request_arg',
									'items'             => [
										'type'       => 'object',
										'properties' => [
											'class' => [
												'type'     => 'string',
												'required' => true,
												'context'  => [ 'view', 'edit' ],
												'description' => __( 'The shipping class/label.', 'google-listings-and-ads' ),
												'validate_callback' => 'rest_validate_request_arg',
											],
											'rate'  => [
												'type'     => 'number',
												'required' => true,
												'minimum'  => 0,
												'context'  => [ 'view', 'edit' ],
												'description' => __( 'The shipping rate.', 'google-listings-and-ads' ),
												'validate_callback' => 'rest_validate_request_arg',
											],
										],
									],
								],
							],
						],
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

	/**
	 * Retrieves all of the registered additional fields for a given object-type.
	 *
	 * @param string $object_type Optional. The object type.
	 *
	 * @return array Registered additional fields (if any), empty array if none or if the object type could
	 *               not be inferred.
	 */
	protected function get_additional_fields( $object_type = null ): array {
		$fields            = parent::get_additional_fields( $object_type );
		$fields['country'] = [
			'schema'       => [
				'type'        => 'string',
				'description' => __( 'Country in which the shipping rate applies.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'get_callback' => function( $fields ) {
				return $this->iso3166_data_provider->alpha2( $fields['country_code'] )['name'];
			},
		];

		return $fields;
	}
}
