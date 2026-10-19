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
	 *
	 * @param string $property Search Console property identifier (`siteUrl`).
	 * @param array  $body     Search Analytics query request body.
	 *
	 * @return array The API response.
	 * @throws SearchConsoleApiException On a non-2xx Search Analytics API response.
	 */
	private function query_search_analytics( string $property, array $body ): array {
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
