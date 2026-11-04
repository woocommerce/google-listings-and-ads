<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsProductReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\MicroTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\ApiCore\ApiException;
use Google\ApiCore\Page;
use Google\Ads\GoogleAds\V23\Common\Segments;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V23\Services\SearchGoogleAdsResponse;
use DateTime;
use DateTimeInterface;

/**
 * Class AdsReport
 *
 * ContainerAware used for:
 * - AdsCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsReport implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use MicroTrait;
	use OptionsAwareTrait;
	use ReportTrait;

	/** Supported interval types. */
	private const INTERVALS = [ 'day', 'week', 'month', 'quarter', 'year' ];

	/** How long the per-report lock is held before it auto-expires (guards against a crashed request). */
	private const REPORT_LOCK_TTL_SECONDS = 30;

	/** How long, in microseconds, to wait between polls for another request's cached result. */
	private const REPORT_LOCK_POLL_INTERVAL = 200000;

	/** Maximum number of polls to wait for another in-flight identical request (poll interval × this). */
	private const REPORT_LOCK_MAX_POLLS = 10;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Have we completed the conversion to PMax campaigns.
	 *
	 * @var bool
	 */
	protected $has_converted;

	/**
	 * AdsReport constructor.
	 *
	 * @param GoogleAdsClient $client
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
	}

	/**
	 * Get report data for campaigns.
	 *
	 * @param string $type Report type (campaigns or products).
	 * @param array  $args Query arguments.
	 *
	 * @return array
	 * @throws ExceptionWithResponseData If the report data can't be retrieved.
	 * @throws InvalidValue When an invalid interval type is given.
	 */
	public function get_report_data( string $type, array $args ): array {
		$ads_id       = (string) $this->options->get_ads_id();
		$cache_key    = 'gla_ads_report_' . $type . '_' . md5(
			wp_json_encode(
				[
					'ads_id' => $ads_id,
					'args'   => $this->normalize_args_for_cache( $args ),
				]
			)
		);
		$cached_value = get_transient( $cache_key );

		if ( is_array( $cached_value ) ) {
			return $cached_value;
		}

		if ( false !== $cached_value ) {
			delete_transient( $cache_key );
		}

		// If an identical report is already being computed (e.g. multi-tab refresh or an
		// overlapping cron run), wait briefly for its cached result. This is a best-effort
		// optimisation that may avoid running duplicate expensive queries - get_transient()
		// and set_transient() are not atomic, so it is not a hard mutex. Each query returns a
		// bounded number of rows, so a rare duplicate is a lost optimisation, not a memory risk.
		$lock_key = $cache_key . '_lock';
		if ( get_transient( $lock_key ) ) {
			for ( $poll = 0; $poll < self::REPORT_LOCK_MAX_POLLS; $poll++ ) {
				usleep( self::REPORT_LOCK_POLL_INTERVAL );

				$cached_value = get_transient( $cache_key );
				if ( is_array( $cached_value ) ) {
					return $cached_value;
				}
			}
			// The in-flight request did not finish in time; fall through and compute it here.
		}

		// Tag the lock with a per-request token so the finally block only clears our own lock,
		// never one a later request acquired after ours expired or was polled out.
		$lock_token = uniqid( '', true );
		set_transient( $lock_key, $lock_token, self::REPORT_LOCK_TTL_SECONDS );

		try {
			// An unknown interval would add no date segment to the intervals query below, so
			// reject it before spending any Google Ads API calls.
			if ( ! empty( $args['interval'] ) && ! in_array( $args['interval'], self::INTERVALS, true ) ) {
				throw InvalidValue::not_in_allowed_list( $args['interval'], self::INTERVALS );
			}

			$this->has_converted = 'converted' === $this->container->get( AdsCampaign::class )->get_campaign_convert_status();

			// This service is shared, so clear anything left over from a previous report.
			$this->report_data = [];

			// The report has three parts: the products/campaigns table, the totals (summary
			// cards) and the intervals (chart). Each part is fetched with its own query that
			// returns only the rows it needs, so the totals and the chart always cover the
			// whole date range, and only the table is capped at per_page rows.

			// 1. Table: one row per product or campaign for the whole date range (no date
			// segment). The query keeps the per_page LIMIT, which bounds memory use.
			$table_args = $args;
			unset( $table_args['interval'] );

			$table_page = $this->get_report_page( $type, $table_args, true );
			$this->read_report_page(
				$table_page,
				(int) ( $args['per_page'] ?? 0 ),
				function ( GoogleAdsRow $row ) use ( $type, $args ) {
					$this->add_report_row( $type, $row, $args );
				}
			);

			if ( $table_page->hasNextPage() ) {
				$this->report_data['next_page'] = $table_page->getNextPageToken();
			}

			// The totals and intervals queries aggregate across all products or campaigns, so
			// the table-only arguments (sorting, row limit, page token) don't apply to them.
			$aggregate_args = $args;
			unset( $aggregate_args['orderby'], $aggregate_args['order'], $aggregate_args['per_page'], $aggregate_args['next_page'] );

			// 2. Totals: no product, campaign or date segment, so Google Ads sums everything
			// into a single row. (When filtered by IDs, the ID column has to stay selected,
			// so there is one row per ID and they are summed here.)
			if ( ! empty( $args['fields'] ) ) {
				$this->init_report_totals( $args['fields'] );

				$totals_args = $aggregate_args;
				unset( $totals_args['interval'] );

				$this->read_report_page(
					$this->get_report_page( $type, $totals_args, false ),
					0,
					function ( GoogleAdsRow $row ) use ( $args ) {
						$this->increase_report_totals( $this->get_report_row_metrics( $row, $args ) );
					}
				);
			}

			// 3. Intervals: segmented by date only, so one row per day (or week, month...).
			// Rows for the same interval (one per ID when filtered by IDs) are summed here.
			if ( ! empty( $args['interval'] ) ) {
				$this->read_report_page(
					$this->get_report_page( $type, $aggregate_args, false ),
					0,
					function ( GoogleAdsRow $row ) use ( $args ) {
						if ( ! $row->getSegments() ) {
							return;
						}

						$interval = $this->get_segment_interval( $args['interval'], $row->getSegments() );

						$this->increase_report_data(
							'intervals',
							$interval,
							[
								'interval'  => $interval,
								'subtotals' => $this->get_report_row_metrics( $row, $args ),
							]
						);
					}
				);
			}

			// Sort intervals to generate an ordered graph.
			if ( isset( $this->report_data['intervals'] ) ) {
				ksort( $this->report_data['intervals'] );
			}

			$this->remove_report_indexes( [ 'products', 'campaigns', 'intervals' ] );

			set_transient( $cache_key, $this->report_data, HOUR_IN_SECONDS );

			return $this->report_data;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to retrieve report data: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[
					'errors'            => $errors,
					'report_type'       => $type,
					'report_query_args' => $args,
				]
			);
		} finally {
			// Only clear the lock if it is still ours: a later request may have taken it over
			// after our TTL expired, and deleting that would defeat its own de-duplication.
			if ( get_transient( $lock_key ) === $lock_token ) {
				delete_transient( $lock_key );
			}
		}
	}

	/**
	 * Normalize query args for stable cache key generation.
	 *
	 * Converts DateTime values to 'Y-m-d' strings (matching how ReportQueryTrait
	 * formats them for the actual query) and applies a recursive ksort so that
	 * argument key ordering does not affect the cache key.
	 *
	 * @param array $args Raw query arguments.
	 * @return array Normalized args safe for hashing.
	 */
	private function normalize_args_for_cache( array $args ): array {
		array_walk_recursive(
			$args,
			function ( &$value ) {
				if ( $value instanceof DateTimeInterface ) {
					$value = $value->format( 'Y-m-d' );
				}
			}
		);

		$sort_recursive = function ( array &$arr ) use ( &$sort_recursive ) {
			ksort( $arr );
			foreach ( $arr as &$value ) {
				if ( is_array( $value ) ) {
					$sort_recursive( $value );
				}
			}
		};
		$sort_recursive( $args );

		return $args;
	}

	/**
	 * Run a report query and return its first page of results.
	 *
	 * Only the first page is used: the table has a LIMIT, and the totals and intervals
	 * queries return far fewer rows than a page holds.
	 *
	 * @since x.x.x
	 *
	 * @param string $type               Report type (campaigns or products).
	 * @param array  $args               Query arguments.
	 * @param bool   $include_dimensions Whether to segment the rows by product or campaign.
	 *
	 * @return Page
	 * @throws ApiException If the search call fails.
	 */
	private function get_report_page( string $type, array $args, bool $include_dimensions ): Page {
		if ( 'products' === $type ) {
			$query = new AdsProductReportQuery( $args, $include_dimensions );
		} else {
			$query = new AdsCampaignReportQuery( $args, $include_dimensions );
		}

		return $query
			->set_client( $this->client, $this->options->get_ads_id() )
			->get_results()
			->getPage();
	}

	/**
	 * Pass each row of a results page to $add_row, and flag the report as truncated when
	 * Google Ads matched more rows than were read.
	 *
	 * @since x.x.x
	 *
	 * @param Page     $page     Results page.
	 * @param int      $max_rows Maximum number of rows to read. 0 means no maximum.
	 * @param callable $add_row  Callback which adds a single row to the report.
	 */
	private function read_report_page( Page $page, int $max_rows, callable $add_row ): void {
		$rows_read = 0;

		// getIterator() only walks this page; iterateAllElements() would fetch every page.
		foreach ( $page->getIterator() as $row ) {
			// Defensive cap on top of the GAQL LIMIT, so an oversized page can never build an
			// unbounded report in memory.
			if ( $max_rows > 0 && $rows_read >= $max_rows ) {
				break;
			}

			$add_row( $row );
			++$rows_read;
		}

		// Google's total results count ignores the LIMIT clause, so if it is higher than what
		// we read, some rows were left out and the report is partial. (With a LIMIT, Google
		// returns no next page token, so that can't be used to detect it.)
		$response      = $page->getResponseObject();
		$total_results = $response instanceof SearchGoogleAdsResponse ? (int) $response->getTotalResultsCount() : 0;

		if ( $total_results > $rows_read ) {
			$this->report_data['truncated'] = true;
		}
	}

	/**
	 * Add a products or campaigns table row to the report.
	 *
	 * Since x.x.x this only adds the table row. The totals and intervals come from their own
	 * queries in get_report_data().
	 *
	 * @param string       $type Report type (campaigns or products).
	 * @param GoogleAdsRow $row  Report row.
	 * @param array        $args Request arguments.
	 */
	protected function add_report_row( string $type, GoogleAdsRow $row, array $args ) {
		$campaign = $row->getCampaign();
		$segments = $row->getSegments();
		$metrics  = $this->get_report_row_metrics( $row, $args );

		if ( 'products' === $type && $segments ) {
			$product_id = $segments->getProductItemId();
			$this->increase_report_data(
				'products',
				(string) $product_id,
				[
					'id'        => $product_id,
					'name'      => $segments->getProductTitle(),
					'subtotals' => $metrics,
				]
			);
		}

		if ( 'campaigns' === $type && $campaign ) {
			$campaign_id   = $campaign->getId();
			$campaign_name = $campaign->getName();
			$campaign_type = CampaignType::label( $campaign->getAdvertisingChannelType() );
			$is_converted  = $this->has_converted && CampaignType::PERFORMANCE_MAX !== $campaign_type;

			$this->increase_report_data(
				'campaigns',
				(string) $campaign_id,
				[
					'id'          => $campaign_id,
					'name'        => $campaign_name,
					'status'      => CampaignStatus::label( $campaign->getStatus() ),
					'isConverted' => $is_converted,
					'subtotals'   => $metrics,
				]
			);
		}
	}

	/**
	 * Get metrics for a report row.
	 *
	 * @param GoogleAdsRow $row  Report row.
	 * @param array        $args Request arguments.
	 *
	 * @return array
	 */
	protected function get_report_row_metrics( GoogleAdsRow $row, array $args ): array {
		$metrics = $row->getMetrics();

		if ( ! $metrics || empty( $args['fields'] ) ) {
			return [];
		}

		$data = [];
		foreach ( $args['fields'] as $field ) {
			switch ( $field ) {
				case 'clicks':
					$data['clicks'] = $metrics->getClicks();
					break;
				case 'impressions':
					$data['impressions'] = $metrics->getImpressions();
					break;
				case 'spend':
					$data['spend'] = $this->from_micro( $metrics->getCostMicros() );
					break;
				case 'sales':
					$data['sales'] = $metrics->getConversionsValue();
					break;
				case 'conversions':
					$data['conversions'] = $metrics->getConversions();
					break;
			}
		}

		return $data;
	}

	/**
	 * Get a unique interval index based on the segments data.
	 *
	 * Types:
	 * day     = <year>-<month>-<day>
	 * week    = <year>-<weeknumber>
	 * month   = <year>-<month>
	 * quarter = <year>-<quarter>
	 * year    = <year>
	 *
	 * @param string   $interval Interval type.
	 * @param Segments $segments Report segment data.
	 *
	 * @return string
	 * @throws InvalidValue When invalid interval type is given.
	 */
	protected function get_segment_interval( string $interval, Segments $segments ): string {
		switch ( $interval ) {
			case 'day':
				$date = new DateTime( $segments->getDate() );
				break;
			case 'week':
				$date = new DateTime( $segments->getWeek() );
				break;
			case 'month':
				$date = new DateTime( $segments->getMonth() );
				break;
			case 'quarter':
				$date = new DateTime( $segments->getQuarter() );
				break;
			case 'year':
				$date = DateTime::createFromFormat( 'Y', (string) $segments->getYear() );
				break;
			default:
				throw InvalidValue::not_in_allowed_list( $interval, self::INTERVALS );
		}
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
