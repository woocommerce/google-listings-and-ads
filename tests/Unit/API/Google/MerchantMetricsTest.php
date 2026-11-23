<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use Google\Ads\GoogleAds\V23\Common\Metrics as AdMetrics;
use Google\Ads\GoogleAds\V23\Resources\Campaign;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V23\Services\Client\GoogleAdsServiceClient;
use Google\Ads\GoogleAds\V23\Services\SearchGoogleAdsRequest;
use Google\ApiCore\ApiException;
use Google\ApiCore\Page;
use Google\ApiCore\PagedListResponse;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantMetricsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantMetricsTest extends UnitTest {

	/**
	 * Google Ads CampaignServingStatus values.
	 * https://developers.google.com/google-ads/api/reference/rpc/v23/CampaignServingStatusEnum.CampaignServingStatus
	 */
	protected const SERVING_STATUS_SERVING = 2;
	protected const SERVING_STATUS_ENDED   = 4;

	/** @var MockObject|MerchantApiClient $mapi_client */
	protected $mapi_client;

	/** @var MockObject|GoogleAdsClient $ads_client */
	protected $ads_client;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var Transients $transients */
	protected $transients;

	/** @var MerchantMetrics $metrics */
	protected $metrics;

	/** @var string $tomorrow */
	protected $tomorrow;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mapi_client = $this->createMock( MerchantApiClient::class );
		$this->ads_client  = $this->createMock( GoogleAdsClient::class );

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->transients = new Transients();
		$this->metrics    = new MerchantMetrics( $this->mapi_client, $this->ads_client, new WP(), $this->transients );
		$this->metrics->set_options_object( $this->options );

		$this->tomorrow = ( new DateTime( 'tomorrow', wp_timezone() ) )->format( 'Y-m-d' );
	}

	public function test_get_free_listing_metrics() {
		$test_merchant_id = 432;
		$this->options->method( 'get_merchant_id' )->willReturn( $test_merchant_id );

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->with(
				"reports/v1/accounts/{$test_merchant_id}/reports:search",
				[
					'query' => "SELECT product_performance_view.clicks,product_performance_view.impressions FROM product_performance_view WHERE product_performance_view.marketing_method = 'ORGANIC' AND product_performance_view.date BETWEEN '2020-01-01' AND '{$this->tomorrow}'",
				]
			)
			->willReturn(
				[
					'results' => [
						[
							'productPerformanceView' => [
								'clicks'      => 3,
								'impressions' => 123,
							],
						],
					],
				]
			);

		$this->assertSame(
			[
				'clicks'      => 3,
				'impressions' => 123,
			],
			$this->metrics->get_free_listing_metrics()
		);
	}

	public function test_get_free_listing_metrics_with_no_results() {
		$this->options->method( 'get_merchant_id' )->willReturn( 1 );

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->willReturn( [ 'results' => [] ] );

		$this->assertSame( [], $this->metrics->get_free_listing_metrics() );
	}

	public function test_get_free_listing_metrics_with_no_merchant_id() {
		$this->options->method( 'get_merchant_id' )->willReturn( 0 );

		$this->assertSame( [], $this->metrics->get_free_listing_metrics() );
	}

	public function test_get_ads_metrics() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );

		$metrics = new AdMetrics();
		$metrics->setConversions( 1 );
		$metrics->setClicks( 2 );
		$metrics->setImpressions( 3 );

		$ads_row = $this->createMock( GoogleAdsRow::class );
		$ads_row->method( 'getMetrics' )->willReturn( $metrics );

		$generator = $this->createMock( \Iterator::class );
		$generator->method( 'current' )->willReturn( $ads_row );

		$response_page = $this->createMock( Page::class );
		$response_page->method( 'getIterator' )->willReturn( $generator );

		$response = $this->createMock( PagedListResponse::class );
		$response->method( 'getPage' )->willReturn( $response_page );

		$google_ads_service_client = $this->createMock( GoogleAdsServiceClient::class );
		$google_ads_service_client->method( 'search' )->willReturn( $response );

		$this->ads_client->method( 'getGoogleAdsServiceClient' )
			->willReturn( $google_ads_service_client );

		$this->assertSame(
			[
				'clicks'      => 2,
				'conversions' => 1,
				'impressions' => 3,
			],
			$this->metrics->get_ads_metrics()
		);
	}

	public function test_get_ads_metrics_with_no_ads_id() {
		$this->options->method( 'get_ads_id' )->willReturn( 0 );

		$this->assertSame( [], $this->metrics->get_ads_metrics() );
	}

	public function test_get_campaign_count_also_caches_serving_count_from_one_query() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->mock_campaign_query(
			[
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_ENDED ],
			]
		);

		$this->assertSame( 3, $this->metrics->get_campaign_count() );
		$this->assertSame( 2, $this->metrics->get_serving_campaign_count() );
	}

	public function test_get_serving_campaign_count_also_caches_total_count_from_one_query() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->mock_campaign_query(
			[
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_ENDED ],
			]
		);

		$this->assertSame( 2, $this->metrics->get_serving_campaign_count() );
		$this->assertSame( 3, $this->metrics->get_campaign_count() );
	}

	public function test_get_serving_campaign_count_excludes_paused_campaign_with_serving_status() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->mock_campaign_query(
			[
				[ CampaignStatus::PAUSED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
			]
		);

		$this->assertSame( 1, $this->metrics->get_serving_campaign_count() );
	}

	public function test_get_serving_campaign_count_returns_cached_value_without_querying() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->transients->set( TransientsInterface::ADS_SERVING_CAMPAIGN_COUNT, 4, HOUR_IN_SECONDS );

		$this->ads_client->expects( $this->never() )->method( 'getGoogleAdsServiceClient' );

		$this->assertSame( 4, $this->metrics->get_serving_campaign_count() );
	}

	public function test_get_serving_campaign_count_queries_when_only_total_count_is_cached() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->transients->set( TransientsInterface::ADS_CAMPAIGN_COUNT, 7, HOUR_IN_SECONDS );
		$this->mock_campaign_query(
			[
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_SERVING ],
				[ CampaignStatus::ENABLED, self::SERVING_STATUS_ENDED ],
			]
		);

		$this->assertSame( 2, $this->metrics->get_serving_campaign_count() );
		$this->assertSame( 3, $this->metrics->get_campaign_count() );
	}

	public function test_get_serving_campaign_count_with_no_ads_id() {
		$this->options->method( 'get_ads_id' )->willReturn( 0 );

		$this->ads_client->expects( $this->never() )->method( 'getGoogleAdsServiceClient' );

		$this->assertSame( 0, $this->metrics->get_serving_campaign_count() );
	}

	public function test_get_serving_campaign_count_returns_null_and_caches_nothing_on_exception() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->mock_campaign_query_exception();

		$this->assertNull( $this->metrics->get_serving_campaign_count() );

		$stored = new Transients();
		$this->assertNull( $stored->get( TransientsInterface::ADS_SERVING_CAMPAIGN_COUNT ) );
		$this->assertNull( $stored->get( TransientsInterface::ADS_CAMPAIGN_COUNT ) );
	}

	public function test_get_campaign_count_caches_zero_on_exception() {
		$this->options->method( 'get_ads_id' )->willReturn( 1 );
		$this->mock_campaign_query_exception();

		$this->assertSame( 0, $this->metrics->get_campaign_count() );
		// The second call is served from the cached 0; the query mock allows only one search.
		$this->assertSame( 0, $this->metrics->get_campaign_count() );

		$this->assertNull( ( new Transients() )->get( TransientsInterface::ADS_SERVING_CAMPAIGN_COUNT ) );
	}

	/**
	 * Mock a single search of non-removed campaigns that selects the serving status.
	 *
	 * @param array $campaigns List of [ CampaignStatus label, serving status number ] pairs.
	 */
	protected function mock_campaign_query( array $campaigns ): void {
		$rows = array_map(
			function ( array $campaign ) {
				[ $status, $serving_status ] = $campaign;

				$campaign_mock = $this->createMock( Campaign::class );
				$campaign_mock->method( 'getStatus' )->willReturn( CampaignStatus::number( $status ) );
				$campaign_mock->method( 'getServingStatus' )->willReturn( $serving_status );

				return ( new GoogleAdsRow() )->setCampaign( $campaign_mock );
			},
			$campaigns
		);

		$response = $this->createMock( PagedListResponse::class );
		$response->method( 'iterateAllElements' )->willReturn( $rows );

		$google_ads_service_client = $this->createMock( GoogleAdsServiceClient::class );
		$google_ads_service_client->expects( $this->once() )
			->method( 'search' )
			->with(
				$this->callback(
					function ( SearchGoogleAdsRequest $request ) {
						$query = $request->getQuery();
						return false !== strpos( $query, 'campaign.serving_status' )
							&& false !== strpos( $query, "campaign.status != 'REMOVED'" );
					}
				)
			)
			->willReturn( $response );

		$this->ads_client->method( 'getGoogleAdsServiceClient' )
			->willReturn( $google_ads_service_client );
	}

	/**
	 * Mock a single campaign search that fails.
	 */
	protected function mock_campaign_query_exception(): void {
		$google_ads_service_client = $this->createMock( GoogleAdsServiceClient::class );
		$google_ads_service_client->expects( $this->once() )
			->method( 'search' )
			->willThrowException( new ApiException( 'API error', 13 ) );

		$this->ads_client->method( 'getGoogleAdsServiceClient' )
			->willReturn( $google_ads_service_client );
	}
}
