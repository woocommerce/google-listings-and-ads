<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ShippingRateSchemaTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ShippingRateController extends BaseController implements ISO3166AwareInterface {

	use ShippingRateSchemaTrait;

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
	 * ShippingRateController constructor.
	 *
	 * @param RESTServer        $server
	 * @param ShippingRateQuery $query
	 */
	public function __construct( RESTServer $server, ShippingRateQuery $query ) {
		parent::__construct( $server );
		$this->query = $query;
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
	 * @since 1.12.0
	 */
	protected function get_update_rate_callback(): callable {
		return function ( Request $request ) {
			$id = (string) $request->get_param( 'id' );

			$rate = $this->get_shipping_rate_by_id( $id );
			if ( empty( $rate ) ) {
				return new Response(
					[
						'message' => __( 'No rate found with the given ID.', 'google-listings-and-ads' ),
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
			$shipping_rate_query = $this->create_query();

			try {
				$data    = $this->prepare_item_for_database( $request );
				$country = $data['country'];

				$existing_query = $this->create_query()->where( 'country', $country );
				$existing       = ! empty( $existing_query->get_results() );

				if ( $existing ) {
					$rate_id = $existing_query->get_results()[0]['id'];
					$shipping_rate_query->update( $data, [ 'id' => $rate_id ] );
				} else {
					$shipping_rate_query->insert( $data );
					$rate_id = $shipping_rate_query->last_insert_id();
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

			// Fetch updated/inserted rate to return in response.
			$rate_response = $this->prepare_item_for_response(
				$this->get_shipping_rate_by_id( (string) $rate_id ),
				$request
			);

			return new Response(
				[
					'status'  => 'success',
					'message' => sprintf(
						/* translators: %s is the country code in ISO 3166-1 alpha-2 format. */
						__( 'Successfully added rate for country: "%s".', 'google-listings-and-ads' ),
						$country
					),
					'rate'    => $rate_response->get_data(),
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

				$rate = $this->get_shipping_rate_by_id( $id );
				if ( empty( $rate ) ) {
					return new Response(
						[
							'message' => __( 'No rate found with the given ID.', 'google-listings-and-ads' ),
							'id'      => $id,
						],
						404
					);
				}

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
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return $this->get_shipping_rate_schema();
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
