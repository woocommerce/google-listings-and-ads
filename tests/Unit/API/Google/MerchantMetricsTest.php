<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
use Google\Ads\GoogleAds\V23\Common\Metrics as AdMetrics;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V23\Services\Client\GoogleAdsServiceClient;
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

	/** @var MockObject|MerchantApiClient $mapi_client */
	protected $mapi_client;

	/** @var MockObject|GoogleAdsClient $ads_client */
	protected $ads_client;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

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

		$this->options = $this->createMock( OptionsInterface::class );
		$this->metrics = new MerchantMetrics( $this->mapi_client, $this->ads_client, new WP(), new Transients() );
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
}
