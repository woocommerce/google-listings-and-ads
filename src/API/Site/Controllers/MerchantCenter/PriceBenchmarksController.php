<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PriceBenchmarks;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class PriceBenchmarksController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PriceBenchmarksController extends BaseController implements ContainerAwareInterface {

	use ContainerAwareTrait;

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/price-benchmarks',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_price_benchmarks_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'mc/price-benchmarks/(?P<id>[\d]+)',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_price_benchmarks_item_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		// Route for price benchmarks summary.
		$this->register_route(
			'mc/price-benchmarks/summary',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_price_benchmarks_summary_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_summary_response_schema_callback(),
			]
		);
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param Request $request REST Request.
	 * @return array
	 */
	protected function prepare_query_arguments( Request $request ): array {
		$args = wp_parse_args(
			array_intersect_key(
				$request->get_query_params(),
				$this->get_collection_params()
			),
			$request->get_default_params()
		);

		return $args;
	}

	/**
	 * Get the callback function for the price benchmarks request.
	 *
	 * @return callable
	 */
	protected function get_price_benchmarks_callback(): callable {
		return function ( Request $request ) {
			try {
				/** @var PriceBenchmarks $price_benchmarks */
				$price_benchmarks = $this->container->get( PriceBenchmarks::class );

				$response_data = $price_benchmarks->get_price_benchmarks_data( $this->prepare_query_arguments( $request ) );

				return new Response( $response_data );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for a specific price benchmark request.
	 *
	 * @return callable
	 */
	protected function get_price_benchmarks_item_callback(): callable {
		return function ( Request $request ) {
			try {
				/** @var PriceBenchmarks $price_benchmarks */
				$price_benchmarks = $this->container->get( PriceBenchmarks::class );

				$id = $request->get_param( 'id' );

				$args = [
					'include' => [ $id ],
				];

				$response_data = $price_benchmarks->get_price_benchmarks_data( $args );

				return new Response( $response_data );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'page'     => [
				'description' => __( 'Current page of the collection.', 'google-listings-and-ads' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'per_page' => [
				'description' => __( 'Maximum number of items returned in the results.', 'google-listings-and-ads' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'search'   => [
				'description' => __( 'Limit results to those matching a string.', 'google-listings-and-ads' ),
				'type'        => 'string',
			],
			'order'    => [
				'description' => __( 'Order sort attribute ascending or descending.', 'google-listings-and-ads' ),
				'type'        => 'string',
				'enum'        => [ 'asc', 'desc' ],
				'default'     => 'desc',
			],
			'orderby'  => [
				'description' => __( 'Sort collection by attribute.', 'google-listings-and-ads' ),
				'type'        => 'string',
				'enum'        => array_keys( PriceBenchmarks::COLUMN_MAP ),
				'default'     => PriceBenchmarks::DEFAULT_ORDERBY,
			],
		];
	}

	/**
	 * Get the query params for a single product.
	 *
	 * @return array
	 */
	public function get_item_params(): array {
		$item_params = [
			'id' => [
				'description' => __( 'The Id of the product.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
		];

		return $item_params;
	}

	/**
	 * Callback for the price benchmarks summary endpoint.
	 *
	 * @return callable
	 */
	protected function get_price_benchmarks_summary_callback(): callable {
		return function () {
			try {
				/** @var PriceBenchmarks $price_benchmarks */
				$price_benchmarks = $this->container->get( PriceBenchmarks::class );

				$summary_data = $price_benchmarks->get_summary();

				return new Response( $summary_data );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the schema for settings endpoints.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'results' => [
				'product'                       => [
					'description' => __( 'Product details.', 'google-listings-and-ads' ),
					'type'        => 'object',
					'properties'  => [
						'id'        => [ 'type' => 'integer' ],
						'thumbnail' => [
							'type'   => 'string',
							'format' => 'uri',
						],
						'title'     => [ 'type' => 'string' ],
					],
				],
				'effectiveness'                 => [
					'description' => __( 'Effectiveness score.', 'google-listings-and-ads' ),
					'type'        => 'integer',
					'enum'        => [ 0, 1, 2, 3 ],
				],
				'country_code'                  => [
					'description' => __( 'Country code.', 'google-listings-and-ads' ),
					'type'        => 'string',
				],
				'currency_code'                 => [
					'description' => __( 'Currency code.', 'google-listings-and-ads' ),
					'type'        => 'string',
				],
				'product_price'                 => [
					'description' => __( 'Current price of the product on Google.', 'google-listings-and-ads' ),
					'type'        => 'number',
				],
				'benchmark_price'               => [
					'description' => __( 'Average benchmark price of the product on Google.', 'google-listings-and-ads' ),
					'type'        => 'number',
				],
				'benchmark_price_currency_code' => [
					'description' => __( 'Benchmark price currency code.', 'google-listings-and-ads' ),
					'type'        => 'string',
				],
				'price_gap'                     => [
					'description' => __( 'Price gap between the product price and the benchmark price on Google.', 'google-listings-and-ads' ),
					'type'        => 'number',
				],
				'suggested_price'               => [
					'description' => __( 'Suggested price for the product.', 'google-listings-and-ads' ),
					'type'        => 'number',
				],
				'suggested_price_currency_code' => [
					'description' => __( 'Suggested price currency code.', 'google-listings-and-ads' ),
					'type'        => 'string',
				],
				'clicks'                        => [
					'description' => __( 'Current clicks.', 'google-listings-and-ads' ),
					'type'        => 'integer',
				],
				'impressions'                   => [
					'description' => __( 'Current impressions.', 'google-listings-and-ads' ),
					'type'        => 'integer',
				],
				'ctr'                           => [
					'description' => __( 'Click-through rate.', 'google-listings-and-ads' ),
					'type'        => [ 'string', 'null' ],
				],
				'conversions'                   => [
					'description' => __( 'Current conversions.', 'google-listings-and-ads' ),
					'type'        => 'integer',
				],
				'predicted_impressions_change'  => [
					'description' => __( 'Expected uplift in impressions (fraction).', 'google-listings-and-ads' ),
					'type'        => [ 'string', 'null' ],
				],
				'predicted_clicks_change'       => [
					'description' => __( 'Expected uplift in clicks (fraction).', 'google-listings-and-ads' ),
					'type'        => [ 'string', 'null' ],
				],
				'predicted_conversions_change'  => [
					'description' => __( 'Expected uplift in conversions (fraction).', 'google-listings-and-ads' ),
					'type'        => [ 'string', 'null' ],
				],
				'price_compared_with_benchmark' => [
					'description' => __( 'Comparison of price with benchmark.', 'google-listings-and-ads' ),
					'type'        => 'integer',
					'enum'        => [ 0, 1, 2, 3 ],
				],
			],
			'total'   => [
				'description' => __( 'The total number of benchmarks that are available.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
		];
	}

	/**
	 * Get the schema for the summary endpoint.
	 *
	 * @see BaseController::get_api_response_schema_callback
	 *
	 * @return array
	 */
	protected function get_summary_response_schema_callback(): callable {
		return function () {
			return $this->prepare_item_schema( $this->get_summary_schema_properties(), $this->get_summary_schema_title() );
		};
	}

	/**
	 * Get the schema properties for the summary endpoint.
	 *
	 * @return array
	 */
	protected function get_summary_schema_properties(): array {
		return [
			'total_products' => [
				'description' => __( 'Total number of products represented in the Google report.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
			'price_similar'  => [
				'description' => __( 'Total number of products with similar prices to benchmark data.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
			'price_higher'   => [
				'description' => __( 'Total number of products with higher prices to benchmark data.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
			'price_lower'    => [
				'description' => __( 'Total number of products with lower prices to benchmark data.', 'google-listings-and-ads' ),
				'type'        => 'integer',
			],
			'price_unknown'  => [
				'description' => __( 'Total number of products without price benchmark data.', 'google-listings-and-ads' ),
				'type'        => 'integer',
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
		return 'price_benchmarks';
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_summary_schema_title(): string {
		return 'price_benchmarks_summary';
	}
}
