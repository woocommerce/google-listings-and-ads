<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantReportTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantReportTest extends UnitTest {

	/** @var MockObject|MerchantApiClient $mapi_client */
	protected $mapi_client;

	/** @var MockObject|ProductHelper $product_helper */
	protected $product_helper;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MerchantReport $merchant_report */
	protected $merchant_report;

	/** Merchant ID */
	protected const MERCHANT_ID = 432;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mapi_client    = $this->createMock( MerchantApiClient::class );
		$this->product_helper = $this->createMock( ProductHelper::class );

		$this->options         = $this->createMock( OptionsInterface::class );
		$this->merchant_report = new MerchantReport( $this->product_helper, $this->mapi_client );
		$this->merchant_report->set_options_object( $this->options );

		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );
	}

	public function test_get_product_view_report() {
		$page_size = 800;

		add_filter(
			'woocommerce_gla_product_view_report_page_size',
			function () use ( $page_size ) {
				return $page_size;
			}
		);

		$this->product_helper->method( 'get_wc_product_id' )->willReturnCallback(
			function ( $mc_id ) {
				$map = [
					'en~US~gla_882' => 882,
					'en~US~gla_883' => 883,
				];
				return $map[ $mc_id ] ?? 0;
			}
		);

		$date = [
			'year'  => 2026,
			'month' => 6,
			'day'   => 27,
		];

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->with(
				'reports/v1/accounts/' . self::MERCHANT_ID . '/reports:search',
				[
					'query'    => 'SELECT product_view.id,product_view.expiration_date,product_view.aggregated_reporting_context_status FROM product_view',
					'pageSize' => $page_size,
				]
			)
			->willReturn(
				[
					'results'       => [
						[
							'productView' => [
								'id'             => 'en~US~gla_882',
								'aggregatedReportingContextStatus' => 'ELIGIBLE',
								'expirationDate' => $date,
							],
						],
						[
							'productView' => [
								'id'             => 'en~US~gla_883',
								'aggregatedReportingContextStatus' => 'NOT_ELIGIBLE_OR_DISAPPROVED',
								'expirationDate' => $date,
							],
						],
						[
							'productView' => [
								'id'             => 'en~US~external',
								'aggregatedReportingContextStatus' => 'ELIGIBLE',
								'expirationDate' => $date,
							],
						],
					],
					'nextPageToken' => null,
				]
			);

		$expected_date = DateTime::createFromFormat( 'Y-m-d|', '2026-6-27' );

		$this->assertEquals(
			[
				'statuses'        => [
					882 => [
						'mc_id'           => 'en~US~gla_882',
						'product_id'      => 882,
						'status'          => MCStatus::APPROVED,
						'expiration_date' => $expected_date,
					],
					883 => [
						'mc_id'           => 'en~US~gla_883',
						'product_id'      => 883,
						'status'          => MCStatus::DISAPPROVED,
						'expiration_date' => $expected_date,
					],
				],
				'next_page_token' => null,
			],
			$this->merchant_report->get_product_view_report()
		);
	}

	public function test_get_product_view_report_handles_missing_expiration_date() {
		$this->product_helper->method( 'get_wc_product_id' )->willReturnCallback(
			function ( $mc_id ) {
				return 'en~US~gla_884' === $mc_id ? 884 : 0;
			}
		);

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->willReturn(
				[
					'results'       => [
						[
							// A product returned without an expirationDate must not break parsing.
							'productView' => [
								'id' => 'en~US~gla_884',
								'aggregatedReportingContextStatus' => 'ELIGIBLE',
							],
						],
					],
					'nextPageToken' => null,
				]
			);

		$report = $this->merchant_report->get_product_view_report();

		$this->assertNull( $report['statuses'][884]['expiration_date'] );
	}

	public function test_get_product_view_report_applies_response_filter() {
		$this->product_helper->method( 'get_wc_product_id' )->willReturnCallback(
			function ( $mc_id ) {
				return 'en~US~gla_885' === $mc_id ? 885 : 0;
			}
		);

		$this->mapi_client->method( 'post' )->willReturn(
			[
				'results'       => [],
				'nextPageToken' => null,
			]
		);

		add_filter(
			'woocommerce_gla_mapi_report_query_response',
			function () {
				return [
					'results'       => [
						[
							'productView' => [
								'id' => 'en~US~gla_885',
								'aggregatedReportingContextStatus' => 'ELIGIBLE',
							],
						],
					],
					'nextPageToken' => null,
				];
			}
		);

		$report = $this->merchant_report->get_product_view_report();

		$this->assertArrayHasKey( 885, $report['statuses'] );
		$this->assertSame( MCStatus::APPROVED, $report['statuses'][885]['status'] );
	}

	public function test_get_product_view_report_with_exception() {
		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to retrieve Product View Report.' );
		$this->merchant_report->get_product_view_report();
	}


	public function test_get_report_data_products() {
		$args = [
			'fields'   => [ 'clicks', 'impressions' ],
			'interval' => 'day',
			'after'    => '2026-06-01',
			'before'   => '2026-06-02',
		];

		$this->product_helper->method( 'get_wc_product_title' )->willReturn( 'Product 882' );

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->with(
				'reports/v1/accounts/' . self::MERCHANT_ID . '/reports:search',
				[
					'query' => "SELECT product_performance_view.offer_id,product_performance_view.clicks,product_performance_view.impressions,product_performance_view.date FROM product_performance_view WHERE product_performance_view.date BETWEEN '2026-06-01' AND '2026-06-02' AND product_performance_view.marketing_method = 'ORGANIC'",
				]
			)
			->willReturn(
				[
					'results'       => [
						[
							'productPerformanceView' => [
								'offerId'     => 'gla_882',
								'date'        => [
									'year'  => 2026,
									'month' => 6,
									'day'   => 1,
								],
								'clicks'      => 5,
								'impressions' => 100,
							],
						],
					],
					'nextPageToken' => null,
				]
			);

		$data = $this->merchant_report->get_report_data( 'products', $args );

		$this->assertSame(
			[
				'clicks'      => 5,
				'impressions' => 100,
			],
			$data['totals']
		);
		$this->assertCount( 1, $data['products'] );
		$this->assertSame( 'gla_882', $data['products'][0]['id'] );
		$this->assertSame( 'Product 882', $data['products'][0]['name'] );
		$this->assertSame(
			[
				'clicks'      => 5,
				'impressions' => 100,
			],
			$data['products'][0]['subtotals']
		);
		$this->assertCount( 1, $data['intervals'] );
	}

	public function test_get_report_data_free_listings() {
		$args = [
			'fields'   => [ 'clicks', 'impressions' ],
			'interval' => 'day',
			'after'    => '2026-06-01',
			'before'   => '2026-06-02',
		];

		$this->mapi_client->expects( $this->once() )
			->method( 'post' )
			->willReturn(
				[
					'results'       => [
						[
							// Free listings rows have no offerId; they aggregate into totals and intervals.
							'productPerformanceView' => [
								'date'        => [
									'year'  => 2026,
									'month' => 6,
									'day'   => 1,
								],
								'clicks'      => 5,
								'impressions' => 100,
							],
						],
					],
					'nextPageToken' => null,
				]
			);

		$data = $this->merchant_report->get_report_data( 'free_listings', $args );

		$this->assertSame(
			[
				'clicks'      => 5,
				'impressions' => 100,
			],
			$data['totals']
		);
		$this->assertCount( 1, $data['free_listings'] );
		$this->assertSame(
			[
				'clicks'      => 5,
				'impressions' => 100,
			],
			$data['free_listings'][0]['subtotals']
		);
		$this->assertCount( 1, $data['intervals'] );
	}
}
