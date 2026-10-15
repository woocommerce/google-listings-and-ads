<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class TopProductPagesService
 *
 * Builds the list of top organic product landing pages, ranked by clicks, with each
 * Search Console page URL resolved to its WooCommerce product.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class TopProductPagesService {

	/** @var int Default number of products returned. */
	public const DEFAULT_LIMIT = 100;

	/** @var int How many times the display limit is requested from Search Console, since unresolved URLs are dropped. */
	protected const OVER_FETCH_MULTIPLIER = 3;

	/** @var int Largest `rowLimit` the Search Analytics API accepts. */
	protected const MAX_ROW_LIMIT = 25000;

	/** @var SearchConsoleApiClient */
	protected $client;

	/** @var Connection */
	protected $connection;

	/** @var PageProductResolver */
	protected $resolver;

	/** @var ProductHelper */
	protected $product_helper;

	/**
	 * TopProductPagesService constructor.
	 *
	 * @param SearchConsoleApiClient $client
	 * @param Connection             $connection
	 * @param PageProductResolver    $resolver
	 * @param ProductHelper          $product_helper
	 */
	public function __construct( SearchConsoleApiClient $client, Connection $connection, PageProductResolver $resolver, ProductHelper $product_helper ) {
		$this->client         = $client;
		$this->connection     = $connection;
		$this->resolver       = $resolver;
		$this->product_helper = $product_helper;
	}

	/**
	 * Get the top products by clicks for a date range.
	 *
	 * @param string $start_date First day of the range, `Y-m-d`.
	 * @param string $end_date   Last day of the range, `Y-m-d`.
	 *
	 * @return array[] Rows of `product_id`, `name`, `clicks`, `impressions`, `ctr` and `position`, most clicks first.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics response.
	 * @throws Exception When no Search Console property is connected.
	 */
	public function get_top_product_pages( string $start_date, string $end_date ): array {
		$property = $this->connection->get_connection_data()['property'] ?? null;

		if ( empty( $property ) ) {
			throw new Exception( __( 'Search Console is not connected.', 'google-listings-and-ads' ), 400 );
		}

		$limit    = $this->get_limit();
		$response = $this->client->post(
			'sites/' . rawurlencode( $property ) . '/searchAnalytics/query',
			[
				'startDate'  => $start_date,
				'endDate'    => $end_date,
				'dimensions' => [ 'page' ],
				'rowLimit'   => min( self::MAX_ROW_LIMIT, $limit * self::OVER_FETCH_MULTIPLIER ),
			]
		);

		$products = $this->group_rows_by_product( $response['rows'] ?? [] );

		usort(
			$products,
			function ( array $a, array $b ) {
				return [ $b['clicks'], $b['impressions'] ] <=> [ $a['clicks'], $a['impressions'] ];
			}
		);

		return array_map(
			function ( array $product ) {
				unset( $product['weighted_position'] );
				return $product;
			},
			array_slice( $products, 0, $limit )
		);
	}

	/**
	 * Resolve each row to a product, drop the ones that don't resolve, and combine the
	 * rows of pages that belong to the same product.
	 *
	 * @param array[] $rows Search Analytics rows keyed by page URL.
	 *
	 * @return array[]
	 */
	protected function group_rows_by_product( array $rows ): array {
		$products = [];

		foreach ( $rows as $row ) {
			$url = $row['keys'][0] ?? null;

			if ( ! is_string( $url ) ) {
				continue;
			}

			$product_id = $this->resolver->resolve_product_id( $url );

			if ( null === $product_id ) {
				continue;
			}

			$clicks      = (int) ( $row['clicks'] ?? 0 );
			$impressions = (int) ( $row['impressions'] ?? 0 );

			if ( ! isset( $products[ $product_id ] ) ) {
				$products[ $product_id ] = [
					'product_id'        => $product_id,
					'name'              => $this->product_helper->get_wc_product( $product_id )->get_name(),
					'clicks'            => 0,
					'impressions'       => 0,
					'ctr'               => 0.0,
					'position'          => 0.0,
					'weighted_position' => 0.0,
				];
			}

			$products[ $product_id ]['clicks']            += $clicks;
			$products[ $product_id ]['impressions']       += $impressions;
			$products[ $product_id ]['weighted_position'] += (float) ( $row['position'] ?? 0 ) * $impressions;
		}

		foreach ( $products as &$product ) {
			if ( $product['impressions'] > 0 ) {
				$product['ctr']      = $product['clicks'] / $product['impressions'];
				$product['position'] = $product['weighted_position'] / $product['impressions'];
			}
		}
		unset( $product );

		return array_values( $products );
	}

	/**
	 * Get the maximum number of products to return.
	 *
	 * @return int
	 */
	protected function get_limit(): int {
		/**
		 * Filters the maximum number of top product pages returned from Search Console.
		 *
		 * @param int $limit Maximum number of products. Defaults to 100.
		 */
		$limit = apply_filters( 'woocommerce_gla_search_console_top_product_pages_limit', self::DEFAULT_LIMIT );

		return is_numeric( $limit ) && (int) $limit > 0 ? (int) $limit : self::DEFAULT_LIMIT;
	}
}
