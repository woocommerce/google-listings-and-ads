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
use Psr\Container\ContainerInterface;
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

	/** @var ContainerInterface */
	protected $container;

	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'mc/shipping/rates';

	/**
	 * BaseController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->container = $container;
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
	}

	/**
	 * Get the callback function for returning the endpoint results.
	 *
	 * @return callable
	 */
	protected function get_read_rates_callback(): callable {
		return function( Request $request ) {
			$rates = $this->get_all_shipping_rates();
			$items = [];
			foreach ( $rates as $rate ) {
				$data = $this->prepare_item_for_response(
					[
						'country_code' => $rate['country'],
						'currency'     => $rate['currency'],
						'rate'         => $rate['rate'],
					],
					$request
				);

				$items[ $rate['country'] ] = $this->prepare_response_for_collection( $data );
			}

			return $items;
		};
	}

	/**
	 * @return callable
	 */
	protected function get_read_rate_callback(): callable {
		return function( Request $request ) {
			$country = $request->get_param( 'country_code' );
			$rate    = $this->get_shipping_rate_for_country( $country );
			if ( empty( $rate ) ) {
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
					'country_code' => $rate['country'],
					'currency'     => $rate['currency'],
					'rate'         => $rate['rate'],
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
		return function( Request $request ) {
			$query        = $this->get_query_object();
			$country_code = $request->get_param( 'country_code' );
			$existing     = ! empty( $query->where( 'country', $country_code )->get_results() );

			try {
				$data = [
					'country'  => $country_code,
					'currency' => $request->get_param( 'currency' ),
					'rate'     => $request->get_param( 'rate' ),
				];

				if ( $existing ) {
					$query->update(
						$data,
						[
							'id' => $query->get_results()[0]['id'],
						]
					);
				} else {
					$query->insert( $data );
				}

				return new Response(
					[
						'status'  => 'success',
						'message' => sprintf(
							/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
							__( 'Successfully added rate for country: "%s".', 'google-listings-and-ads' ),
							$country_code
						),
					],
					201
				);
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
		};
	}

	/**
	 * @return callable
	 */
	protected function get_delete_rate_callback(): callable {
		return function( Request $request ) {
			try {
				$country_code = $request->get_param( 'country_code' );
				$this->get_query_object()->delete( 'country', $country_code );

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
	 * @return array
	 */
	protected function get_all_shipping_rates(): array {
		return $this->get_query_object()->set_limit( 100 )->get_results();
	}

	/**
	 * @param string $country
	 *
	 * @return array
	 */
	protected function get_shipping_rate_for_country( string $country ): array {
		return $this->get_query_object()->where( 'country', $country )->get_results();
	}

	/**
	 * Get the shipping time query object.
	 *
	 * @return ShippingRateQuery
	 */
	protected function get_query_object(): ShippingRateQuery {
		return $this->container->get( ShippingRateQuery::class );
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
			'currency'     => [
				'type'              => 'string',
				'description'       => __( 'The currency to use for the shipping rate.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'default'           => 'USD', // todo: default to store currency.
			],
			'rate'         => [
				'type'              => 'number',
				'description'       => __( 'The shipping rate.', 'google-listings-and-ads' ),
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
