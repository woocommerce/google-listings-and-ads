<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractTopPagesService
 *
 * Builds a list of top organic landing pages, ranked by clicks, from the Search Console
 * `page` dimension. Each report decides how a page URL is grouped and named.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
abstract class AbstractTopPagesService {

	/** @var int How many times the display limit is requested from Search Console, since some URLs are dropped. */
	protected const OVER_FETCH_MULTIPLIER = 3;

	/** @var int Seconds a result is cached for. */
	protected const CACHE_LIFETIME = HOUR_IN_SECONDS;

	/** @var int Seconds an empty result is cached for, short so new content shows up soon. */
	protected const EMPTY_RESULT_CACHE_LIFETIME = 5 * MINUTE_IN_SECONDS;

	/** @var int Most results kept cached at once, so the shared transient can't grow with every date range tried. */
	protected const MAX_CACHED_RESULTS = 10;

	/** @var SearchAnalyticsService */
	protected $search_analytics;

	/** @var TransientsInterface */
	protected $transients;

	/**
	 * AbstractTopPagesService constructor.
	 *
	 * @param SearchAnalyticsService $search_analytics
	 * @param TransientsInterface    $transients
	 */
	public function __construct( SearchAnalyticsService $search_analytics, TransientsInterface $transients ) {
		$this->search_analytics = $search_analytics;
		$this->transients       = $transients;
	}

	/**
	 * Get the transient the report's results are cached in.
	 *
	 * @return string One of the {@see TransientsInterface} constants.
	 */
	abstract protected function get_cache_name(): string;

	/**
	 * Get the key that groups a page URL with the other URLs of the same page.
	 *
	 * @param string $url Absolute page URL as reported by Search Console.
	 *
	 * @return string|null The group key, or null to leave the URL out of the report.
	 */
	abstract protected function get_page_key( string $url ): ?string;

	/**
	 * Get the fields that identify a page in the report, such as its ID and name.
	 *
	 * Called once per group, with the first URL seen for it.
	 *
	 * @param string $key Group key from {@see self::get_page_key()}.
	 * @param string $url Absolute page URL as reported by Search Console.
	 *
	 * @return array
	 */
	abstract protected function describe_page( string $key, string $url ): array;

	/**
	 * Get the top pages of a property by clicks for a date range.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int               $limit    Maximum number of pages, already kept within the API's limits.
	 *
	 * @return array[] Up to the limit of rows, each with the fields from {@see self::describe_page()}
	 *                 followed by `clicks`, `impressions`, `ctr` and `position`, sorted by clicks descending.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	protected function get_top_pages( string $property, DateTimeInterface $after, DateTimeInterface $before, int $limit ): array {
		$start_date = $after->format( 'Y-m-d' );
		$end_date   = $before->format( 'Y-m-d' );
		$cache_key  = md5( implode( '|', [ $property, $start_date, $end_date, $limit ] ) );
		$cached     = $this->transients->get( $this->get_cache_name(), [] );

		if ( isset( $cached[ $cache_key ]['pages'], $cached[ $cache_key ]['expires'] ) && $cached[ $cache_key ]['expires'] > time() ) {
			return $cached[ $cache_key ]['pages'];
		}

		$response = $this->search_analytics->query_search_analytics(
			$property,
			[
				'startDate'  => $start_date,
				'endDate'    => $end_date,
				'dimensions' => [ 'page' ],
				'rowLimit'   => min( SearchAnalyticsService::API_MAX_ROW_LIMIT, $limit * self::OVER_FETCH_MULTIPLIER ),
			]
		);

		$pages = $this->group_rows_by_page( $response['rows'] ?? [] );

		usort(
			$pages,
			function ( array $a, array $b ) {
				return [ $b['clicks'], $b['impressions'] ] <=> [ $a['clicks'], $a['impressions'] ];
			}
		);

		$top_pages = array_map(
			function ( array $page ) {
				unset( $page['weighted_position'] );
				return $page;
			},
			array_slice( $pages, 0, $limit )
		);

		// Resolving URLs to pages is the costly part, so keep the result for a short while.
		// Each entry carries its own expiry, because writing the shared transient renews its lifetime for every entry in it.
		// An empty result may be a gap in the data or in the URL matching, so it is kept for less time.
		$cached = array_filter(
			is_array( $cached ) ? $cached : [],
			function ( $entry ) {
				return isset( $entry['pages'], $entry['expires'] ) && $entry['expires'] > time();
			}
		);

		$cached[ $cache_key ] = [
			'expires' => time() + ( empty( $top_pages ) ? self::EMPTY_RESULT_CACHE_LIFETIME : self::CACHE_LIFETIME ),
			'pages'   => $top_pages,
		];
		$cached               = array_slice( $cached, -self::MAX_CACHED_RESULTS, null, true );
		$this->transients->set( $this->get_cache_name(), $cached, self::CACHE_LIFETIME );

		return $top_pages;
	}

	/**
	 * Group each row with the other rows of the same page, drop the rows that have no page,
	 * and combine the metrics of each group.
	 *
	 * @param array[] $rows Search Analytics rows keyed by page URL.
	 *
	 * @return array[]
	 */
	protected function group_rows_by_page( array $rows ): array {
		$pages = [];

		foreach ( $rows as $row ) {
			$url = $row['keys'][0] ?? null;

			if ( ! is_string( $url ) ) {
				continue;
			}

			$key = $this->get_page_key( $url );

			if ( null === $key ) {
				continue;
			}

			$clicks      = (int) ( $row['clicks'] ?? 0 );
			$impressions = (int) ( $row['impressions'] ?? 0 );

			if ( ! isset( $pages[ $key ] ) ) {
				$pages[ $key ] = $this->describe_page( $key, $url ) + [
					'clicks'            => 0,
					'impressions'       => 0,
					'ctr'               => 0.0,
					'position'          => 0.0,
					'weighted_position' => 0.0,
				];
			}

			$pages[ $key ]['clicks']            += $clicks;
			$pages[ $key ]['impressions']       += $impressions;
			$pages[ $key ]['weighted_position'] += (float) ( $row['position'] ?? 0 ) * $impressions;
		}

		foreach ( $pages as &$page ) {
			if ( $page['impressions'] > 0 ) {
				$page['ctr']      = $page['clicks'] / $page['impressions'];
				$page['position'] = $page['weighted_position'] / $page['impressions'];
			}
		}
		unset( $page );

		return array_values( $pages );
	}
}
