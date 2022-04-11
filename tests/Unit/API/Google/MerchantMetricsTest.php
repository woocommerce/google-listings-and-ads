<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use Google\Ads\GoogleAds\V9\Common\Metrics as AdMetrics;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V9\Services\GoogleAdsServiceClient;
use Google\ApiCore\Page;
use Google\ApiCore\PagedListResponse;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Metrics;
use Google\Service\ShoppingContent\ReportRow;
use Google\Service\ShoppingContent\SearchRequest;
use Google\Service\ShoppingContent\SearchResponse;
use Google\Service\ShoppingContent\Resource\Reports;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property  MockObject|ShoppingContent  $shopping_client
 * @property  MockObject|GoogleAdsClient  $ads_client
 * @property  MockObject|OptionsInterface $options
 * @property  MerchantMetrics             $metrics
 * @property  string                      $tomorrow
 */
class MerchantMetricsTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->shopping_client          = $this->createMock( ShoppingContent::class );
		$this->ads_client               = $this->createMock( GoogleAdsClient::class );
		$this->shopping_client->reports = $this->createMock( Reports::class );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->metrics = new MerchantMetrics( $this->shopping_client, $this->ads_client, new WP(), new Transients() );
		$this->metrics->set_options_object( $this->options );

		$this->tomorrow = ( new DateTime( 'tomorrow', wp_timezone() ) )->format( 'Y-m-d' );
	}

	public function test_get_free_listing_metrics() {
		$test_merchant_id = 432;
		$this->options->method( 'get_merchant_id' )->willReturn( $test_merchant_id );

		$metrics = new Metrics();
		$metrics->setClicks( 3 );
		$metrics->setImpressions( 123 );

		$report_row = new ReportRow();
		$report_row->setMetrics( $metrics );

		$response = $this->createMock( SearchResponse::class );
		$response->expects( $this->any() )
		         ->method( 'getResults' )
		         ->willReturn( [ $report_row ] );

		$search_request = new SearchRequest();
		$search_request->setQuery(
			"SELECT metrics.clicks,metrics.impressions FROM MerchantPerformanceView WHERE segments.program = 'FREE_PRODUCT_LISTING' AND segments.date BETWEEN '2020-01-01' AND '{$this->tomorrow}'"
		);

		$this->shopping_client->reports->expects( $this->once() )
		                               ->method( 'search' )
		                               ->with( $test_merchant_id, $search_request )
		                               ->willReturn( $response );

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

		$response = $this->createMock( SearchResponse::class );
		$response->expects( $this->once() )
		         ->method( 'getResults' )
		         ->willReturn( [] );

		$this->shopping_client->reports->expects( $this->once() )
		                               ->method( 'search' )
		                               ->willReturn( $response );

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

		$adsRow = $this->createMock( GoogleAdsRow::class );
		$adsRow->method( 'getMetrics' )->willReturn( $metrics );

		$generator = $this->createMock( \Iterator::class );
		$generator->method( 'current' )->willReturn( $adsRow );

		$response_page = $this->createMock( Page::class );
		$response_page->method( 'getIterator' )->willReturn( $generator );

		$response = $this->createMock( PagedListResponse::class );
		$response->method( 'getPage' )->willReturn( $response_page );

		$googleAdsServiceClient = $this->createMock( GoogleAdsServiceClient::class );
		$googleAdsServiceClient->method( 'search' )->willReturn( $response );

		$this->ads_client->method( 'getGoogleAdsServiceClient' )
		                 ->willReturn( $googleAdsServiceClient );

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

}
