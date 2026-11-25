<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCampaignQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\MerchantFreeListingReportQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use DateTime;
use Exception;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
use Google\ApiCore\PagedListResponse;

/**
 * Class MerchantMetrics
 *
 * @since   1.7.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class MerchantMetrics implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The Merchant API client.
	 *
	 * @var MerchantApiClient
	 */
	protected $mapi_client;

	/**
	 * The Google ads client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $ads_client;

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * @var TransientsInterface
	 */
	protected $transients;

	protected const MAX_QUERY_START_DATE = '2020-01-01';

	/**
	 * Google Ads CampaignServingStatus value for a campaign that is serving.
	 *
	 * Kept as a local value because the build prunes Google Ads enum classes that src/ does not
	 * import (bin/GoogleAdsCleanupServices.php), so the CampaignServingStatus enum is not vendored.
	 * https://developers.google.com/google-ads/api/reference/rpc/v23/CampaignServingStatusEnum.CampaignServingStatus
	 *
	 * @since x.x.x
	 */
	private const CAMPAIGN_SERVING_STATUS_SERVING = 2;

	/**
	 * MerchantMetrics constructor.
	 *
	 * The $mapi_client type hint is intentionally left off. During a plugin
	 * upgrade WordPress overwrites files in place mid-request, so this class can
	 * be loaded fresh (new signature) while the DI provider is still the old one
	 * in memory, injecting the legacy ShoppingContent client. Omitting the hint
	 * lets that transient construction succeed instead of fatally erroring; the
	 * following request resolves the correct MerchantApiClient. See the guard in
	 * RESTControllers::register_controllers() for the complementary protection.
	 *
	 * @param MerchantApiClient   $mapi_client
	 * @param GoogleAdsClient     $ads_client
	 * @param WP                  $wp
	 * @param TransientsInterface $transients
	 */
	public function __construct( $mapi_client, GoogleAdsClient $ads_client, WP $wp, TransientsInterface $transients ) {
		$this->mapi_client = $mapi_client;
		$this->ads_client  = $ads_client;
		$this->wp          = $wp;
		$this->transients  = $transients;
	}

	/**
	 * Get free listing metrics.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *      @type int $clicks Number of free clicks.
	 *      @type int $impressions NUmber of free impressions.
	 *
	 * @throws Exception When unable to get clicks data.
	 */
	public function get_free_listing_metrics(): array {
		if ( ! $this->options->get_merchant_id() ) {
			// Merchant account not set up
			return [];
		}

		// Google API requires a date clause to be set but there doesn't seem to be any limits on how wide the range
		$query = ( new MerchantFreeListingReportQuery( [] ) )
			->set_client( $this->mapi_client, $this->options->get_merchant_id() )
			->where_date_between( self::MAX_QUERY_START_DATE, $this->get_tomorrow() )
			->fields( [ 'clicks', 'impressions' ] );

		$response = $query->get_results();

		if ( empty( $response['results'] ) ) {
			return [];
		}

		$view = $response['results'][0]['productPerformanceView'] ?? [];

		return [
			'clicks'      => (int) ( $view['clicks'] ?? 0 ),
			'impressions' => (int) ( $view['impressions'] ?? 0 ),
		];
	}

	/**
	 * Get free listing metrics but cached for 12 hours.
	 *
	 * PLEASE NOTE: These metrics will not be 100% accurate since there is no invalidation apart from the 12 hour refresh.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *      @type int $clicks Number of free clicks.
	 *      @type int $impressions NUmber of free impressions.
	 *
	 * @throws Exception When unable to get data.
	 */
	public function get_cached_free_listing_metrics(): array {
		$value = $this->transients->get( TransientsInterface::FREE_LISTING_METRICS );

		if ( $value === null ) {
			$value = $this->get_free_listing_metrics();
			$this->transients->set( TransientsInterface::FREE_LISTING_METRICS, $value, HOUR_IN_SECONDS * 12 );
		}

		return $value;
	}

	/**
	 * Get ads metrics across all campaigns.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *
	 * @throws Exception When unable to get data.
	 */
	public function get_ads_metrics(): array {
		if ( ! $this->options->get_ads_id() ) {
			// Ads account not set up
			return [];
		}

		// Google API requires a date clause to be set but there doesn't seem to be any limits on how wide the range
		$query = ( new AdsCampaignReportQuery( [] ) )
			->set_client( $this->ads_client, $this->options->get_ads_id() )
			->where_date_between( self::MAX_QUERY_START_DATE, $this->get_tomorrow() )
			->fields( [ 'clicks', 'conversions', 'impressions' ] );

		/** @var PagedListResponse $response */
		$response = $query->get_results();
		$page     = $response->getPage();

		if ( $page && $page->getIterator()->current() ) {
			/** @var GoogleAdsRow $row */
			$row = $page->getIterator()->current();

			$metrics = $row->getMetrics();
			if ( $metrics ) {
				return [
					'clicks'      => $metrics->getClicks(),
					'conversions' => (int) $metrics->getConversions(),
					'impressions' => $metrics->getImpressions(),
				];
			}
		}

		return [];
	}

	/**
	 * Get ads metrics across all campaigns but cached for 12 hours.
	 *
	 * PLEASE NOTE: These metrics will not be 100% accurate since there is no invalidation apart from the 12 hour refresh.
	 *
	 * @return array Of metrics or empty if no metrics were available.
	 *
	 * @throws Exception When unable to get data.
	 */
	public function get_cached_ads_metrics(): array {
		$value = $this->transients->get( TransientsInterface::ADS_METRICS );

		if ( $value === null ) {
			$value = $this->get_ads_metrics();
			$this->transients->set( TransientsInterface::ADS_METRICS, $value, HOUR_IN_SECONDS * 12 );
		}

		return $value;
	}

	/**
	 * Return amount of active campaigns for the connected Ads account.
	 *
	 * @since 2.5.11
	 *
	 * @return int
	 */
	public function get_campaign_count(): int {
		if ( ! $this->options->get_ads_id() ) {
			return 0;
		}

		$cached_count = $this->transients->get( TransientsInterface::ADS_CAMPAIGN_COUNT );
		if ( null !== $cached_count ) {
			return (int) $cached_count;
		}

		try {
			return $this->query_campaign_counts()['total'];
		} catch ( Exception $e ) {
			$this->transients->set( TransientsInterface::ADS_CAMPAIGN_COUNT, 0, HOUR_IN_SECONDS * 12 );
			return 0;
		}
	}

	/**
	 * Return the number of campaigns in the connected Ads account that are currently serving.
	 *
	 * A campaign is serving when it is enabled and Google reports its serving status as SERVING,
	 * so paused, ended, pending, and suspended campaigns are not counted.
	 * Cached for 12 hours, together with the campaign count from the same query.
	 *
	 * @since x.x.x
	 *
	 * @return int|null Number of serving campaigns, 0 if no Ads account is connected, or null if the query failed.
	 */
	public function get_serving_campaign_count(): ?int {
		if ( ! $this->options->get_ads_id() ) {
			return 0;
		}

		$cached_count = $this->transients->get( TransientsInterface::ADS_SERVING_CAMPAIGN_COUNT );
		if ( null !== $cached_count ) {
			return (int) $cached_count;
		}

		try {
			return $this->query_campaign_counts()['serving'];
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Query the non-removed campaigns of the connected Ads account and cache their counts for 12 hours.
	 *
	 * @since x.x.x
	 *
	 * @return array {
	 *     @type int $total   Number of campaigns that are not removed.
	 *     @type int $serving Number of enabled campaigns whose serving status is SERVING.
	 * }
	 *
	 * @throws Exception When the campaign query fails. Nothing is cached in that case.
	 */
	private function query_campaign_counts(): array {
		$query = ( new AdsCampaignQuery() )->set_client( $this->ads_client, $this->options->get_ads_id() );
		$query->add_columns( [ 'campaign.serving_status' ] );
		$query->where( 'campaign.status', 'REMOVED', '!=' );

		$total   = 0;
		$serving = 0;

		// Iterate through all paged results (total results count is not set).
		foreach ( $query->get_results()->iterateAllElements() as $row ) {
			++$total;

			$campaign = $row->getCampaign();
			if (
				CampaignStatus::ENABLED === CampaignStatus::label( $campaign->getStatus() ) &&
				self::CAMPAIGN_SERVING_STATUS_SERVING === $campaign->getServingStatus()
			) {
				++$serving;
			}
		}

		$this->transients->set( TransientsInterface::ADS_CAMPAIGN_COUNT, $total, HOUR_IN_SECONDS * 12 );
		$this->transients->set( TransientsInterface::ADS_SERVING_CAMPAIGN_COUNT, $serving, HOUR_IN_SECONDS * 12 );

		return [
			'total'   => $total,
			'serving' => $serving,
		];
	}

	/**
	 * Get tomorrow's date to ensure we include any metrics from the current day.
	 *
	 * @return string
	 */
	protected function get_tomorrow(): string {
		return ( new DateTime( 'tomorrow', $this->wp->wp_timezone() ) )->format( 'Y-m-d' );
	}
}
