<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingTimeController extends BaseController implements ISO3166AwareInterface {

	use CountryCodeTrait;

	/** @var ContainerInterface */
	protected $container;

	/**
	 * The base for routes in this controller.
	 *
	 * @var string
	 */
	protected $route_base = 'mc/shipping/times';

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
					'callback'            => $this->get_read_times_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_create_time_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_args_schema(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			"{$this->route_base}/(?P<country_code>\\w{2})",
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_time_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
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
		return function ( Request $request ) {
			$times = $this->get_all_shipping_times();
			$items = [];
			foreach ( $times as $time ) {
				$data = $this->prepare_item_for_response(
					[
						'country_code' => $time['country'],
						'time'         => $time['time'],
						'max_time'     => $time['max_time'],
					],
					$request
				);

				$items[ $time['country'] ] = $this->prepare_response_for_collection( $data );
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
		return function ( Request $request ) {
			$country = $request->get_param( 'country_code' );
			$time    = $this->get_shipping_time_for_country( $country );
			if ( empty( $time ) ) {
				return new Response(
					[
						'message' => __( 'No time available.', 'google-listings-and-ads' ),
						'country' => $country,
					],
					404
				);
			}

			return $this->prepare_item_for_response(
				[
					'country_code' => $time[0]['country'],
					'time'         => $time[0]['time'],
					'max_time'     => $time[0]['max_time'],
				],
				$request
			);
		};
	}

	/**
	 * Get the callback to crate a new time.
	 *
	 * @return callable
	 */
	protected function get_create_time_callback(): callable {
		return function ( Request $request ) {
			$query        = $this->get_query_object();
			$country_code = $request->get_param( 'country_code' );
			$existing     = ! empty( $query->where( 'country', $country_code )->get_results() );

			try {
				$data = [
					'country'  => $country_code,
					'time'     => $request->get_param( 'time' ),
					'max_time' => $request->get_param( 'max_time' ),
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
							__( 'Successfully added time for country: "%s".', 'google-listings-and-ads' ),
							$country_code
						),
					],
					201
				);
			} catch ( InvalidQuery $e ) {
				return $this->error_from_exception(
					$e,
					'gla_error_creating_shipping_time',
					[
						'code'    => 400,
						'message' => $e->getMessage(),
					]
				);
			}
		};
	}

	/**
	 * Get the callback function for deleting a time.
	 *
	 * @return callable
	 */
	protected function get_delete_time_callback(): callable {
		return function ( Request $request ) {
			try {
				$country_code = $request->get_param( 'country_code' );
				$this->get_query_object()->delete( 'country', $country_code );

				return [
					'status'  => 'success',
					'message' => sprintf(
					/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'Successfully deleted the time for country: "%s".', 'google-listings-and-ads' ),
						$country_code
					),
				];
			} catch ( InvalidQuery $e ) {
				return $this->error_from_exception(
					$e,
					'gla_error_deleting_shipping_time',
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
	protected function get_all_shipping_times(): array {
		return $this->get_query_object()->set_limit( 100 )->get_results();
	}

	/**
	 * @param string $country
	 *
	 * @return array
	 */
	protected function get_shipping_time_for_country( string $country ): array {
		return $this->get_query_object()->where( 'country', $country )->get_results();
	}

	/**
	 * Get the shipping time query object.
	 *
	 * @return ShippingTimeQuery
	 */
	protected function get_query_object(): ShippingTimeQuery {
		return $this->container->get( ShippingTimeQuery::class );
	}

	/**
	 * Get the item schema for the controller.
	 *
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
			'time'         => [
				'type'              => 'integer',
				'description'       => __( 'The minimum shipping time in days.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => [ $this, 'validate_shipping_times' ],
			],
			'max_time'     => [
				'type'              => 'integer',
				'description'       => __( 'The maximum shipping time in days.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => [ $this, 'validate_shipping_times' ],
			],
		];
	}

	/**
	 * Get the args schema for the controller.
	 *
	 * @return array
	 */
	protected function get_args_schema(): array {
		$schema                         = $this->get_schema_properties();
		$schema['time']['required']     = true;
		$schema['max_time']['required'] = true;
		return $schema;
	}

	/**
	 * Validate the shipping times.
	 *
	 * @param mixed   $value
	 * @param Request $request
	 * @param string  $param
	 *
	 * @return WP_Error|true
	 */
	public function validate_shipping_times( $value, $request, $param ) {
		$time     = $request->get_param( 'time' );
		$max_time = $request->get_param( 'max_time' );

		if ( rest_is_integer( $value ) === false ) {
			return new WP_Error(
				'rest_invalid_type',
				/* translators: 1: Parameter, 2: Type name. */
				sprintf( __( '%1$s is not of type %2$s.', 'google-listings-and-ads' ), $param, 'integer' ),
				[ 'param' => $param ]
			);
		}

		if ( $value < 0 ) {
			return new WP_Error( 'invalid_shipping_times', __( 'Shipping times cannot be negative.', 'google-listings-and-ads' ), [ 'param' => $param ] );
		}

		if ( $time > $max_time ) {
			return new WP_Error( 'invalid_shipping_times', __( 'The minimum shipping time cannot be greater than the maximum shipping time.', 'google-listings-and-ads' ), [ 'param' => $param ] );
		}

		return true;
	}


	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'shipping_times';
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
				'description' => __( 'Country in which the shipping time applies.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'get_callback' => function ( $fields ) {
				return $this->iso3166_data_provider->alpha2( $fields['country_code'] )['name'];
			},
		];

		return $fields;
	}
}
