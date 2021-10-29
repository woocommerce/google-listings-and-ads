<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use DateTime;
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
 * @property  MockObject|ShoppingContent  $service
 * @property  MockObject|OptionsInterface $options
 * @property  MockObject|WP               $wp
 * @property  MerchantMetrics             $metrics
 */
class MerchantMetricsTest extends UnitTest {

	protected const TEST_MERCHANT_ID = 123;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->wp               = new WP();
		$this->service          = $this->createMock( ShoppingContent::class );
		$this->service->reports = $this->createMock( Reports::class );

		$this->options = $this->createMock( OptionsInterface::class );
		$this->metrics = new MerchantMetrics( $this->service, $this->wp );
		$this->metrics->set_options_object( $this->options );

		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MERCHANT_ID );
	}

	public function test_get_free_listing_clicks() {
		$metrics = new Metrics();
		$metrics->setClicks( 3 );

		$report_row = new ReportRow();
		$report_row->setMetrics( $metrics );

		$response = $this->createMock( SearchResponse::class );
		$response->expects( $this->any() )
		         ->method( 'getResults' )
		         ->willReturn( [ $report_row ] );

		$today = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );

		$search_request = new SearchRequest();
		$search_request->setQuery(
			"SELECT metrics.clicks FROM MerchantPerformanceView WHERE segments.program = 'FREE_PRODUCT_LISTING' AND segments.date BETWEEN '2020-01-01' AND '{$today}'"
		);

		$this->service->reports->expects( $this->once() )
		                       ->method( 'search' )
		                       ->with( self::TEST_MERCHANT_ID, $search_request )
		                       ->willReturn( $response );

		$this->assertSame( 3, $this->metrics->get_free_listing_clicks() );
	}

	public function test_get_free_listing_clicks_with_no_results() {
		$response = $this->createMock( SearchResponse::class );
		$response->expects( $this->once() )
		         ->method( 'getResults' )
		         ->willReturn( [] );

		$this->service->reports->expects( $this->once() )
		                       ->method( 'search' )
		                       ->willReturn( $response );

		$this->assertSame( 0, $this->metrics->get_free_listing_clicks() );
	}

}
