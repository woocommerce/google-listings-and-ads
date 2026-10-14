<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole;

use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SearchAnalyticsService
 *
 * Reads organic search performance data from the Search Console Search Analytics API.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\SearchConsole
 */
class SearchAnalyticsService {

	/** @var int Default number of top queries returned. */
	public const DEFAULT_TOP_QUERIES_LIMIT = 100;

	/** @var int Maximum rows the Search Analytics API returns per request. */
	protected const API_MAX_ROW_LIMIT = 25000;

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

		if ( ! is_numeric( $limit ) ) {
			return self::DEFAULT_TOP_QUERIES_LIMIT;
		}

		return min( self::API_MAX_ROW_LIMIT, max( 1, (int) $limit ) );
	}

	/**
	 * Get the top search queries of a property, ranked by clicks.
	 *
	 * @param string            $property Search Console property identifier (`siteUrl`).
	 * @param DateTimeInterface $after    First day of the period.
	 * @param DateTimeInterface $before   Last day of the period.
	 * @param int|null          $limit    Maximum number of rows. Defaults to {@see self::get_top_queries_limit()}.
	 *
	 * @return array[] Up to the limit of rows, each with `query`, `clicks`,
	 *                 `impressions`, `ctr` and `position`, sorted by clicks descending.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	public function get_top_queries( string $property, DateTimeInterface $after, DateTimeInterface $before, ?int $limit = null ): array {
		$limit    = $limit ?? $this->get_top_queries_limit();
		$response = $this->client->post(
			'sites/' . rawurlencode( $property ) . '/searchAnalytics/query',
			[
				'startDate'  => $after->format( 'Y-m-d' ),
				'endDate'    => $before->format( 'Y-m-d' ),
				'dimensions' => [ 'query' ],
				'rowLimit'   => $limit,
			]
		);

		$rows = [];
		foreach ( $response['rows'] ?? [] as $row ) {
			$rows[] = [
				'query'       => (string) ( $row['keys'][0] ?? '' ),
				'clicks'      => (int) ( $row['clicks'] ?? 0 ),
				'impressions' => (int) ( $row['impressions'] ?? 0 ),
				'ctr'         => (float) ( $row['ctr'] ?? 0 ),
				'position'    => (float) ( $row['position'] ?? 0 ),
			];
		}

		// Don't rely on the API's ordering or on it honouring the row limit.
		usort(
			$rows,
			fn( array $a, array $b ) => $b['clicks'] <=> $a['clicks']
		);

		return array_slice( $rows, 0, $limit );
	}
}
