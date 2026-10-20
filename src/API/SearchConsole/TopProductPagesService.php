<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use DateTimeInterface;

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

	/** @var int Seconds a result is cached for. */
	protected const CACHE_LIFETIME = HOUR_IN_SECONDS;

	/** @var int Seconds an empty result is cached for, short so a new product shows up soon. */
	protected const EMPTY_RESULT_CACHE_LIFETIME = 5 * MINUTE_IN_SECONDS;

	/** @var int Most results kept cached at once, so the shared transient can't grow with every date range tried. */
	protected const MAX_CACHED_RESULTS = 10;

	/** @var int Maximum rows the Search Analytics API returns per request. */
	protected const API_MAX_ROW_LIMIT = 25000;

	/** @var SearchAnalyticsService */
	protected $search_analytics;

	/** @var PageProductResolver */
	protected $resolver;

	/** @var ProductHelper */
	protected $product_helper;

	/** @var TransientsInterface */
	protected $transients;

	/**
	 * TopProductPagesService constructor.
	 *
	 * @param SearchAnalyticsService $search_analytics
	 * @param PageProductResolver    $resolver
	 * @param ProductHelper          $product_helper
	 * @param TransientsInterface    $transients
	 */
	public function __construct( SearchAnalyticsService $search_analytics, PageProductResolver $resolver, ProductHelper $product_helper, TransientsInterface $transients ) {
		$this->search_analytics = $search_analytics;
		$this->resolver         = $resolver;
		$this->product_helper   = $product_helper;
		$this->transients       = $transients;
	}

	/**
	 * Get the top products of a property by clicks for a date range.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int|null          $limit    Maximum number of products, kept between 1 and the API's maximum. Defaults to {@see self::get_top_product_pages_limit()}, which falls back to 100 for a filtered value below 1.
	 *
	 * @return array[] Up to the limit of rows, each with `product_id`, `name`, `clicks`,
	 *                 `impressions`, `ctr` and `position`, sorted by clicks descending.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_top_product_pages( string $property, DateTimeInterface $after, DateTimeInterface $before, ?int $limit = null ): array {
		$limit      = null === $limit ? $this->get_top_product_pages_limit() : $this->clamp_limit( $limit );
		$start_date = $after->format( 'Y-m-d' );
		$end_date   = $before->format( 'Y-m-d' );
		$cache_key  = md5( implode( '|', [ $property, $start_date, $end_date, $limit ] ) );
		$cached     = $this->transients->get( TransientsInterface::TOP_PRODUCT_PAGES, [] );

		if ( isset( $cached[ $cache_key ]['products'], $cached[ $cache_key ]['expires'] ) && $cached[ $cache_key ]['expires'] > time() ) {
			return $cached[ $cache_key ]['products'];
		}

		$response = $this->search_analytics->query_search_analytics(
			$property,
			[
				'startDate'  => $start_date,
				'endDate'    => $end_date,
				'dimensions' => [ 'page' ],
				'rowLimit'   => min( self::API_MAX_ROW_LIMIT, $limit * self::OVER_FETCH_MULTIPLIER ),
			]
		);

		$products = $this->group_rows_by_product( $response['rows'] ?? [] );

		usort(
			$products,
			function ( array $a, array $b ) {
				return [ $b['clicks'], $b['impressions'] ] <=> [ $a['clicks'], $a['impressions'] ];
			}
		);

		$top_products = array_map(
			function ( array $product ) {
				unset( $product['weighted_position'] );
				return $product;
			},
			array_slice( $products, 0, $limit )
		);

		// Resolving URLs to products is the costly part, so keep the result for a short while.
		// Each entry carries its own expiry, because writing the shared transient renews its lifetime for every entry in it.
		// An empty result may be a gap in the data or in the URL matching, so it is kept for less time.
		$cached = array_filter(
			is_array( $cached ) ? $cached : [],
			function ( $entry ) {
				return isset( $entry['products'], $entry['expires'] ) && $entry['expires'] > time();
			}
		);

		$cached[ $cache_key ] = [
			'expires'  => time() + ( empty( $top_products ) ? self::EMPTY_RESULT_CACHE_LIFETIME : self::CACHE_LIFETIME ),
			'products' => $top_products,
		];
		$cached               = array_slice( $cached, -self::MAX_CACHED_RESULTS, null, true );
		$this->transients->set( TransientsInterface::TOP_PRODUCT_PAGES, $cached, self::CACHE_LIFETIME );

		return $top_products;
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
	 * Get the number of top products to return.
	 *
	 * The default is a hard limit that protects performance; a developer can raise it with the
	 * `woocommerce_gla_search_console_top_product_pages_limit` filter.
	 *
	 * @return int The default when the filtered value isn't a positive number, otherwise at most the API's maximum row limit.
	 */
	public function get_top_product_pages_limit(): int {
		/**
		 * Filters the maximum number of top product pages returned from Search Console.
		 *
		 * @param int $limit Maximum number of products. Defaults to 100.
		 */
		$limit = apply_filters( 'woocommerce_gla_search_console_top_product_pages_limit', self::DEFAULT_LIMIT );

		if ( ! is_numeric( $limit ) || (int) $limit < 1 ) {
			return self::DEFAULT_LIMIT;
		}

		return $this->clamp_limit( (int) $limit );
	}

	/**
	 * Keep a row limit within what the Search Analytics API accepts.
	 *
	 * @param int $limit
	 *
	 * @return int Between 1 and the API's maximum row limit.
	 */
	protected function clamp_limit( int $limit ): int {
		return min( self::API_MAX_ROW_LIMIT, max( 1, $limit ) );
	}
}
