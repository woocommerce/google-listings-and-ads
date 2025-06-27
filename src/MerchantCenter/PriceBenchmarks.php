<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\MerchantPriceBenchmarksQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class PriceBenchmarks
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class PriceBenchmarks implements ContainerAwareInterface, Service {

	use ContainerAwareTrait;

	/**
	 * Map of column names to their corresponding database fields.
	 *
	 * This is used to map the orderby parameter to the correct database column.
	 *
	 * @var array
	 */
	public const COLUMN_MAP = [
		'effectiveness'   => 'mc_insights_effectiveness',
		'id'              => 'product_id',
		'benchmark_price' => 'mc_price_benchmark_price_micros',
		'product_price'   => 'mc_product_price_micros',
		'suggested_price' => 'mc_insights_suggested_price_micros',
	];

	public const DEFAULT_ORDERBY = 'effectiveness';

	/**
	 * Gets and maps the benchmark and price insights and performance data to the required API response format.
	 *
	 * @param array $args Query arguments.
	 * @return array Mapped response data.
	 */
	protected function get_price_benchmarks_response( $args ): array {
		$mapped_data = [];

		/** @var MerchantPriceBenchmarks $merchant */
		$merchant = $this->container->get( MerchantPriceBenchmarks::class );

		/** @var ProductHelper $product_helper */
		$product_helper = $this->container->get( ProductHelper::class );

		$benchmark_data      = $merchant->get_price_comparisons_data( $args );
		$price_insights_data = $merchant->get_price_insights_data( $args );
		$performance_data    = $merchant->get_merchant_performance_data( $args );

		// Combine all data sets into $mapped_data keyed by product ID.
		foreach ( $benchmark_data ?? [] as $benchmark_result ) {
			$product_id                 = $product_helper->get_wc_product_id( (string) $benchmark_result['offer_id'] );
			$mapped_data[ $product_id ] = [
				'price_competitiveness' => $benchmark_result,
				'price_insights'        => [],
				'performance'           => [],
			];
		}

		// Map price insights data to benchmarks data.
		if ( ! empty( $price_insights_data ) ) {
			// Price insights data.
			foreach ( $price_insights_data as $price_insights_result ) {
				$product_id = $product_helper->get_wc_product_id( (string) $price_insights_result['offer_id'] );
				if ( isset( $mapped_data[ $product_id ] ) ) {
					$mapped_data[ $product_id ]['price_insights'] = $price_insights_result;
				}
			}

			// Performance.
			foreach ( $performance_data as $performance_result ) {
				$product_id = $product_helper->get_wc_product_id( (string) $performance_result['offer_id'] );
				if ( isset( $mapped_data[ $product_id ] ) ) {
					$mapped_data[ $product_id ]['performance'] = $performance_result;
				}
			}
		}

		// Transform $mapped_data into the desired response format using array_map.
		$response_data = array_map(
			function ( $data ) use ( $product_helper ) {
				$price_competitiveness = $data['price_competitiveness'];
				$price_insights        = $data['price_insights'];
				$performance           = $data['performance'];

				// Get the WooCommerce product ID and thumbnail.
				$wc_product_id = $product_helper->get_wc_product_id( (string) $price_competitiveness['offer_id'] );

				// Map the data to the required format.
				return [
					'product_id'                                        => $wc_product_id,
					'mc_product_id'                                     => $price_competitiveness['id'],
					'mc_product_offer_id'                               => $price_competitiveness['offer_id'],
					'mc_price_country_code'                             => $price_competitiveness['country_code'] ?? '',
					'mc_product_currency_code'                          => $price_competitiveness['benchmark_price_currency_code'] ?? '',
					'mc_product_price_micros'                           => $price_competitiveness['price_micros'],
					'mc_price_benchmark_price_micros'                   => $price_competitiveness['benchmark_price_micros'],
					'mc_price_benchmark_price_currency_code'            => $price_competitiveness['benchmark_price_currency_code'] ?? '',
					'mc_insights_suggested_price_micros'                => $price_insights['suggested_price_micros'],
					'mc_insights_suggested_price_currency_code'         => $price_insights['suggested_price_currency_code'] ?? '',
					'mc_insights_predicted_impressions_change_fraction' => $price_insights['predicted_impressions_change_fraction'] ?? '',
					'mc_insights_predicted_clicks_change_fraction'      => $price_insights['predicted_clicks_change_fraction'] ?? '',
					'mc_insights_predicted_conversions_change_fraction' => $price_insights['predicted_conversions_change_fraction'] ?? '',
					'mc_insights_effectiveness'                         => isset( $price_insights['effectiveness'] ) ? $this->get_effectiveness( $price_insights['effectiveness'] ) : '',
					'mc_metrics_clicks'                                 => $performance['clicks'] ?? 0,
					'mc_metrics_impressions'                            => $performance['impressions'] ?? 0,
					'mc_metrics_ctr'                                    => $performance['ctr'] ?? 0,
					'mc_metrics_conversions'                            => $performance['conversions'] ?? 0,
					'price_compared_with_benchmark'                     => $this->price_compared_with_benchmark(
						(int) $price_competitiveness['price_micros'],
						(int) $price_competitiveness['benchmark_price_micros']
					),
				];
			},
			$mapped_data
		);

		return $response_data;
	}

	/**
	 * Retrieves the product thumbnail URL.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return string Product thumbnail URL or an empty string if not found.
	 */
	protected function get_product_thumbnail( int $product_id ): ?string {
		$thumbnail_id = get_post_thumbnail_id( $product_id );

		if ( ! $thumbnail_id ) {
			return '';
		}

		$thumbnail_url = wp_get_attachment_url( $thumbnail_id );

		return $thumbnail_url ?? '';
	}

	/**
	 * Returns the key for the given effectiveness value.
	 *
	 * @param string $value Effectiveness value.
	 * @return int The corresponding key if value is found, otherwise 0.
	 */
	public function get_effectiveness( $value ) {
		$effectiveness_map = [
			'EFFECTIVENESS_UNSPECIFIED' => 0,
			'LOW'                       => 1,
			'MEDIUM'                    => 2,
			'HIGH'                      => 3,
		];

		return $effectiveness_map[ $value ] ?? 0;
	}

	/**
	 * Update price benchmarks by querying the Google Content API and saving the data locally.
	 */
	public function update_price_benchmarks(): void {
		try {
			$benchmarks = $this->get_price_benchmarks_response( [] );

			if ( empty( $benchmarks ) ) {
				return;
			}

			/** @var MerchantPriceBenchmarksQuery $query */
			$query = $this->container->get( MerchantPriceBenchmarksQuery::class );

			// Clear existing data before updating.
			$query->reload_data();

			// Insert new benchmark data.
			foreach ( $benchmarks as $benchmark_item ) {
				$query->insert( $benchmark_item );
			}
		} catch ( \Exception $e ) {
			do_action( 'woocommerce_gla_debug_message', $e->getMessage(), __METHOD__ );
		}
	}

	/**
	 * Compares a given price with a benchmark price.
	 *
	 * This function takes two prices in micros (1,000,000 micros = 1 unit of currency)
	 * and performs a comparison to determine their relationship.
	 *
	 * @param int $price_micros           The price to compare, in micros.
	 * @param int $benchmark_price_micros The benchmark price to compare against, in micros.
	 * @return bool Returns specific price compare group if the price meets the comparison criteria with the benchmark.
	 */
	private function price_compared_with_benchmark( $price_micros, $benchmark_price_micros ) {
		if ( empty( $price_micros ) || empty( $benchmark_price_micros ) ) {
			return 0;
		} elseif ( abs( $price_micros - $benchmark_price_micros ) <= ( $benchmark_price_micros * 0.01 ) ) {
			return 2;
		} elseif ( $price_micros < $benchmark_price_micros ) {
			return 1;
		} elseif ( $price_micros > $benchmark_price_micros ) {
			return 3;
		}
	}

	/**
	 * Get a summary of price benchmarks.
	 *
	 * @return array
	 */
	public function get_summary(): array {
		/** @var MerchantPriceBenchmarksQuery $query */
		$query = $this->container->get( MerchantPriceBenchmarksQuery::class );

		// Get counts for all price comparison groups in one query.
		$benchmark_counts_result = $query->get_price_benchmark_counts();

		// Convert raw DB results to an associative array with all groups.
		$benchmark_counts = $this->get_price_benchmark_counts_data( $benchmark_counts_result );

		return [
			'total_products' => $benchmark_counts['total'] ?? 0, // Total products
			'price_unknown'  => $benchmark_counts[0] ?? 0, // Unknown/missing
			'price_lower'    => $benchmark_counts[1] ?? 0, // Lower price
			'price_similar'  => $benchmark_counts[2] ?? 0, // Similar price
			'price_higher'   => $benchmark_counts[3] ?? 0, // Higher price
		];
	}

	/**
	 * Converts raw benchmark counts from the database to an associative array.
	 *
	 * @param array $rows Raw benchmark counts result from the database.
	 * @return array Associative array with counts for each price comparison group and total.
	 */
	public function get_price_benchmark_counts_data( array $rows ): array {
		// Convert the results to a more usable format
		$counts = [];
		$total  = 0;
		foreach ( $rows as $row ) {
			$price_compared_value            = (int) $row['price_compared_with_benchmark'];
			$counts[ $price_compared_value ] = (int) $row['count'];
			$total                          += $counts[ $price_compared_value ];
		}

		// Make sure all possible values are represented (0, 1, 2, 3)
		$all_values = [ 0, 1, 2, 3 ];
		foreach ( $all_values as $value ) {
			if ( ! isset( $counts[ $value ] ) ) {
				$counts[ $value ] = 0;
			}
		}

		$counts['total'] = $total;

		return $counts;
	}

	/**
	 * Retrieves formatted price benchmarks data from the local database.
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter and paginate results.
	 *
	 *     @type array|null $include  List of product IDs to include. Default null.
	 *     @type int        $page     Offset for the results. Default 1.
	 *     @type int        $per_page Maximum number of items returned. Default 10.
	 *     @type string     $search   Search string to filter results. Default null.
	 *     @type string     $order    Sort order: 'asc' or 'desc'. Default 'desc'.
	 *     @type string     $orderby  Attribute to sort by. Default 'mc_insights_effectiveness'.
	 * }
	 * @return array {
	 *     @type array $results List of formatted price benchmarks.
	 *     @type int   $total   Total number of price benchmarks available.
	 * }
	 */
	public function get_price_benchmarks_data( array $args = [] ): array {
		$defaults = [
			'include'  => null,
			'page'     => 1,
			'per_page' => 10,
			'search'   => null,
			'order'    => 'desc',
			'orderby'  => self::DEFAULT_ORDERBY,
		];

		$args = wp_parse_args( array_intersect_key( $args, $defaults ), $defaults );

		/** @var MerchantPriceBenchmarksQuery $query */
		$query = $this->container->get( MerchantPriceBenchmarksQuery::class );

		// Apply filters.
		if ( ! empty( $args['include'] ) && is_array( $args['include'] ) ) {
			$query->where( 'product_id', $args['include'], 'IN' );
		}

		if ( ! empty( $args['search'] ) ) {
			$search       = trim( $args['search'] );
			$search_query = new \WP_Query(
				[
					'post_type'      => [ 'product' ],
					'post_status'    => 'publish',
					's'              => $search,
					'fields'         => 'ids',
					'posts_per_page' => -1,
				]
			);

			$product_ids = $search_query->posts;

			// If no products found, return empty results.
			if ( empty( $product_ids ) ) {
				return [
					'results' => [],
					'total'   => 0,
				];
			}

			$query->where( 'product_id', $product_ids, 'IN' );
		}

		// Set order and orderby.
		$order = strtoupper( $args['order'] );
		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'DESC';
		}
		$orderby = $this->map_orderby_to_db_value( $args['orderby'] );

		$query->set_order( $orderby, $order );

		// Set offset and limit.
		$query->set_offset( (int) ( $args['page'] - 1 ) * $args['per_page'] );
		$query->set_limit( (int) $args['per_page'] );

		// Get total count before limiting.
		$total = $query->get_count();

		// Get results.
		$rows = $query->get_results();

		// Format results.
		$results = [];
		foreach ( $rows as $row ) {
			$wc_product_id = (int) $row['product_id'];
			$product       = wc_get_product( $wc_product_id );
			$thumbnail     = $this->get_product_thumbnail( $wc_product_id );

			$results[] = [
				'product'                       => [
					'id'        => $wc_product_id,
					'thumbnail' => $thumbnail,
					'title'     => $product instanceof \WC_Product ? $product->get_name() : '',
				],
				'effectiveness'                 => (int) $row['mc_insights_effectiveness'] ?? 0,
				'country_code'                  => $row['mc_price_country_code'] ?? '',
				'currency_code'                 => $row['mc_product_currency_code'] ?? '',
				'product_price'                 => $this->micros_to_float( (int) $row['mc_product_price_micros'] ),
				'benchmark_price'               => $this->micros_to_float( (int) $row['mc_price_benchmark_price_micros'] ),
				'benchmark_price_currency_code' => $row['mc_price_benchmark_price_currency_code'] ?? '',
				'price_gap'                     => $this->micros_to_float( (int) $row['mc_price_benchmark_price_micros'] - (int) $row['mc_product_price_micros'] ),
				'suggested_price'               => $this->micros_to_float( (int) $row['mc_insights_suggested_price_micros'] ),
				'suggested_price_currency_code' => $row['mc_insights_suggested_price_currency_code'] ?? '',
				'predicted_impressions_change'  => $row['mc_insights_predicted_impressions_change_fraction'] ?? '',
				'predicted_clicks_change'       => $row['mc_insights_predicted_clicks_change_fraction'] ?? '',
				'predicted_conversions_change'  => $row['mc_insights_predicted_conversions_change_fraction'] ?? '',
				'clicks'                        => (int) $row['mc_metrics_clicks'] ?? 0,
				'impressions'                   => (int) $row['mc_metrics_impressions'] ?? 0,
				'ctr'                           => $row['mc_metrics_ctr'] ?? 0,
				'conversions'                   => (int) $row['mc_metrics_conversions'] ?? 0,
				'price_compared_with_benchmark' => (int) $row['price_compared_with_benchmark'] ?? 0,
			];
		}

		return [
			'results' => $results,
			'total'   => $total,
		];
	}

	/**
	 * Maps the orderby parameter to the corresponding database column name.
	 *
	 * @param string $order_by The orderby parameter from the request.
	 * @return string The corresponding database column name.
	 */
	private function map_orderby_to_db_value( string $order_by ): string {
		// If the $order_by value is not in the COLUMN_MAP, use the default.
		if ( ! in_array( $order_by, array_keys( self::COLUMN_MAP ), true ) ) {
			$order_by = self::DEFAULT_ORDERBY;
		}

		return self::COLUMN_MAP[ $order_by ];
	}

	/**
	 * Converts a value in micros to a float representation.
	 *
	 * @param int $micros The value in micros (1,000,000 micros = 1 unit of currency).
	 * @return float The converted float value.
	 */
	private function micros_to_float( int $micros ): float {
		// Convert micros to a float value.
		return round( $micros / 1000000, 2 );
	}
}
