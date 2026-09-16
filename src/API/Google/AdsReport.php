<?php
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
use Google\Ads\GoogleAds\V23\Common\Segments;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
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
		// optimisation that may avoid running a duplicate expensive query — get_transient()
		// and set_transient() are not atomic, so it is not a hard mutex. Each query is already
		// bounded by the GAQL LIMIT, so a rare duplicate is a lost optimisation, not a memory risk.
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
			$this->has_converted = 'converted' === $this->container->get( AdsCampaign::class )->get_campaign_convert_status();

			if ( 'products' === $type ) {
				$query = new AdsProductReportQuery( $args );
			} else {
				$query = new AdsCampaignReportQuery( $args );
			}

			$results = $query
				->set_client( $this->client, $this->options->get_ads_id() )
				->get_results();
			$page    = $results->getPage();

			$this->init_report_totals( $args['fields'] ?? [] );

			$per_page  = isset( $args['per_page'] ) ? (int) $args['per_page'] : 0;
			$row_count = 0;

			// Iterate only this page (iterateAllElements would iterate all pages). A GAQL
			// LIMIT (see AdsQuery::query_results) already caps the response at per_page rows;
			// this stop is a defensive guard so a stray oversized page can never build an
			// unbounded report in memory.
			foreach ( $page->getIterator() as $row ) {
				if ( $per_page > 0 && $row_count >= $per_page ) {
					break;
				}

				$this->add_report_row( $type, $row, $args );
				++$row_count;
			}

			if ( $page->hasNextPage() ) {
				$this->report_data['next_page'] = $page->getNextPageToken();
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
	 * Add data for a report row.
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

		if ( $segments && ! empty( $args['interval'] ) ) {
			$interval = $this->get_segment_interval( $args['interval'], $segments );

			$this->increase_report_data(
				'intervals',
				$interval,
				[
					'interval'  => $interval,
					'subtotals' => $metrics,
				]
			);
		}

		$this->increase_report_totals( $metrics );
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
				throw InvalidValue::not_in_allowed_list( $interval, [ 'day', 'week', 'month', 'quarter', 'year' ] );
		}
		return TimeInterval::time_interval_id( $interval, $date );
	}
}
