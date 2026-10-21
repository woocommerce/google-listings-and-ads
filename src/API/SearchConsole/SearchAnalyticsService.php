<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchAnalyticsService
 *
 * Reads organic search performance data from the Search Console Search Analytics API.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class SearchAnalyticsService implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var int Default number of top queries returned. */
	public const DEFAULT_TOP_QUERIES_LIMIT = 100;

	/** @var int Maximum rows the Search Analytics API returns per request. */
	public const API_MAX_ROW_LIMIT = 25000;

	/** @var SearchConsoleApiClient */
	protected $client;

	/**
	 * SearchAnalyticsService constructor.
	 *
	 * @param SearchConsoleApiClient $client
	 */
	public function __construct( SearchConsoleApiClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get the number of top queries to return.
	 *
	 * The default is a hard limit that protects performance; a developer can raise it with the
	 * `woocommerce_gla_search_console_top_queries_limit` filter.
	 *
	 * @return int Between 1 and the API's maximum row limit.
	 */
	public function get_top_queries_limit(): int {
		/**
		 * Filters the maximum number of top search queries returned by the Search Console report.
		 *
		 * @param int $limit Maximum number of queries. Defaults to 100.
		 */
		$limit = apply_filters( 'woocommerce_gla_search_console_top_queries_limit', self::DEFAULT_TOP_QUERIES_LIMIT );

		return self::validate_filtered_limit( $limit, self::DEFAULT_TOP_QUERIES_LIMIT );
	}

	/**
	 * Turn the value a report's limit filter returned into a usable row limit.
	 *
	 * Shared by every report so the same filter value means the same thing in each: a value that
	 * isn't a number falls back to the default, and any other value is kept between 1 and the
	 * API's maximum row limit.
	 *
	 * @param mixed $filtered Value returned by the filter.
	 * @param int   $fallback Limit used when the value isn't a number.
	 *
	 * @return int Between 1 and the API's maximum row limit.
	 */
	public static function validate_filtered_limit( $filtered, int $fallback ): int {
		if ( ! is_numeric( $filtered ) ) {
			return $fallback;
		}

		return self::clamp_limit( (int) $filtered );
	}

	/**
	 * Keep a row limit within what the Search Analytics API accepts.
	 *
	 * @param int $limit
	 *
	 * @return int Between 1 and the API's maximum row limit.
	 */
	public static function clamp_limit( int $limit ): int {
		return min( self::API_MAX_ROW_LIMIT, max( 1, $limit ) );
	}

	/**
	 * Get the top search queries of a property, ranked by clicks.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int|null          $limit    Maximum number of rows, kept within the API's limits. Defaults to {@see self::get_top_queries_limit()}.
	 *
	 * @return array[] Up to the limit of rows, each with `query`, `clicks`,
	 *                 `impressions`, `ctr` and `position`, sorted by clicks descending.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_top_queries( string $property, DateTimeInterface $after, DateTimeInterface $before, ?int $limit = null ): array {
		$limit    = null === $limit ? $this->get_top_queries_limit() : $this->clamp_limit( $limit );
		$response = $this->query_search_analytics(
			$property,
			[
				'startDate'  => $after->format( 'Y-m-d' ),
				'endDate'    => $before->format( 'Y-m-d' ),
				'dimensions' => [ 'query' ],
				'rowLimit'   => $limit,
			]
		);

		$rows = [];
		foreach ( $response['rows'] ?? [] as $row ) {
			$rows[] = [ 'query' => (string) ( $row['keys'][0] ?? '' ) ] + self::map_performance_row( $row );
		}

		// Don't rely on the API's ordering or on it honouring the row limit.
		usort(
			$rows,
			fn( array $a, array $b ) => $b['clicks'] <=> $a['clicks']
		);

		return array_slice( $rows, 0, $limit );
	}

	/**
	 * Get the organic search overview of a property: totals for a date range, plus the same
	 * range broken down by day, so a caller can plot both a summary and a trend from one request.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 *
	 * @return array{totals: array, intervals: array[]} `totals` has `clicks`, `impressions`, `ctr`
	 *                 and `position` for the whole range. `intervals` has one entry per day with
	 *                 data, each `{ interval: string, subtotals: array }`, sorted oldest first; a
	 *                 day with no data is omitted rather than filled with zeros.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_overview( string $property, DateTimeInterface $after, DateTimeInterface $before ): array {
		$date_range = [
			'startDate' => $after->format( 'Y-m-d' ),
			'endDate'   => $before->format( 'Y-m-d' ),
		];

		// A separate, dimensionless request for the aggregate row: CTR and position aren't a plain
		// average of the daily rows below, so the per-day breakdown can't be summed into totals.
		$totals_response = $this->query_search_analytics( $property, $date_range );
		$totals_row      = $totals_response['rows'][0] ?? [];

		$interval_response = $this->query_search_analytics(
			$property,
			$date_range + [ 'dimensions' => [ 'date' ] ]
		);

		$intervals = array_map(
			fn( array $row ) => [
				'interval'  => (string) ( $row['keys'][0] ?? '' ),
				'subtotals' => self::map_performance_row( $row ),
			],
			$interval_response['rows'] ?? []
		);

		usort( $intervals, fn( array $a, array $b ) => $a['interval'] <=> $b['interval'] );

		return [
			'totals'    => self::map_performance_row( $totals_row ),
			'intervals' => $intervals,
		];
	}

	/**
	 * Map a Search Analytics API row to its clicks, impressions, CTR and position.
	 *
	 * CTR and position are meaningless with no impressions — Search Console itself has nothing to
	 * report for them, not a real zero — so both come back `null` rather than `0`. A real `0` would
	 * read as "rank 0" or "0% CTR", both misleadingly better than having no data at all.
	 *
	 * @param array $row A row from a Search Analytics API response.
	 *
	 * @return array `clicks`, `impressions`, and `ctr`/`position` (`null` when there are no impressions).
	 */
	private static function map_performance_row( array $row ): array {
		$impressions = (int) ( $row['impressions'] ?? 0 );

		return [
			'clicks'      => (int) ( $row['clicks'] ?? 0 ),
			'impressions' => $impressions,
			'ctr'         => $impressions ? (float) ( $row['ctr'] ?? 0 ) : null,
			'position'    => $impressions ? (float) ( $row['position'] ?? 0 ) : null,
		];
	}

	/**
	 * Get when Search Analytics data was last fetched for a property.
	 *
	 * @param string $property Search Console property identifier (`siteUrl`).
	 *
	 * @return int|null Unix timestamp, or null when nothing has been fetched for this property yet.
	 */
	public function get_data_last_updated( string $property ): ?int {
		$last_updated = $this->options->get( OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED );

		if ( ! is_array( $last_updated ) || ( $last_updated['property'] ?? null ) !== $property ) {
			return null;
		}

		return isset( $last_updated['timestamp'] ) ? (int) $last_updated['timestamp'] : null;
	}

	/**
	 * Run a Search Analytics query for a property and record when its data was fetched.
	 *
	 * Every report reads through here, so the last-updated time covers all of them.
	 * Public so reports that need their own row handling can still share it.
	 *
	 * @param string $property Search Console property identifier (`siteUrl`).
	 * @param array  $body     Search Analytics query request body.
	 *
	 * @return array The API response.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function query_search_analytics( string $property, array $body ): array {
		$response = $this->client->post( 'sites/' . rawurlencode( $property ) . '/searchAnalytics/query', $body );

		$this->record_data_fetch( $property );

		return $response;
	}

	/**
	 * Store when Search Analytics data was fetched for a property.
	 *
	 * The property is stored with the timestamp, so a timestamp from a previously connected
	 * property is never reported for a different one.
	 *
	 * @param string $property Search Console property identifier (`siteUrl`).
	 */
	private function record_data_fetch( string $property ): void {
		$this->options->update(
			OptionsInterface::SEARCH_CONSOLE_DATA_LAST_UPDATED,
			[
				'property'  => $property,
				'timestamp' => time(),
			]
		);
	}
}
