<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantPriceBenchmarks;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantPriceBenchmarksTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class MerchantPriceBenchmarksTest extends UnitTest {

	/** @var MockObject|MerchantApiClient $client */
	protected $client;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MerchantPriceBenchmarks $price_benchmarks */
	protected $price_benchmarks;

	/** Merchant ID */
	protected const MERCHANT_ID = 432;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->client  = $this->createMock( MerchantApiClient::class );
		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_merchant_id' )->willReturn( self::MERCHANT_ID );

		$this->price_benchmarks = new MerchantPriceBenchmarks( $this->client );
		$this->price_benchmarks->set_options_object( $this->options );
	}

	public function test_get_price_comparisons_data() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'reports/v1/accounts/432/reports:search',
				[
					'query' => 'SELECT price_competitiveness_product_view.id,price_competitiveness_product_view.offer_id,price_competitiveness_product_view.title,price_competitiveness_product_view.price,price_competitiveness_product_view.report_country_code,price_competitiveness_product_view.benchmark_price FROM price_competitiveness_product_view',
				]
			)
			->willReturn(
				[
					'results' => [
						[
							'priceCompetitivenessProductView' => [
								'id'                => 'en~US~gla_29',
								'offerId'           => 'gla_29',
								'title'             => 'Hoodie',
								'price'             => [
									'amountMicros' => '45000000',
									'currencyCode' => 'USD',
								],
								'benchmarkPrice'    => [
									'amountMicros' => '40000000',
									'currencyCode' => 'USD',
								],
								'reportCountryCode' => 'US',
							],
						],
					],
				]
			);

		$results = $this->price_benchmarks->get_price_comparisons_data( [] );

		$this->assertSame(
			[
				[
					'id'                            => 'en~US~gla_29',
					'offer_id'                      => 'gla_29',
					'title'                         => 'Hoodie',
					'price_micros'                  => '45000000',
					'currency_code'                 => 'USD',
					'country_code'                  => 'US',
					'benchmark_price_micros'        => '40000000',
					'benchmark_price_currency_code' => 'USD',
				],
			],
			$results
		);
	}

	public function test_get_price_insights_data() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'reports/v1/accounts/432/reports:search',
				[
					'query' => 'SELECT price_insights_product_view.id,price_insights_product_view.offer_id,price_insights_product_view.title,price_insights_product_view.price,price_insights_product_view.suggested_price,price_insights_product_view.predicted_impressions_change_fraction,price_insights_product_view.predicted_clicks_change_fraction,price_insights_product_view.predicted_conversions_change_fraction,price_insights_product_view.effectiveness FROM price_insights_product_view',
				]
			)
			->willReturn(
				[
					'results' => [
						[
							'priceInsightsProductView' => [
								'id'             => 'en~US~gla_29',
								'offerId'        => 'gla_29',
								'title'          => 'Hoodie',
								'price'          => [
									'amountMicros' => '45000000',
									'currencyCode' => 'USD',
								],
								'suggestedPrice' => [
									'amountMicros' => '42000000',
									'currencyCode' => 'USD',
								],
								'predictedImpressionsChangeFraction' => 0.05,
								'predictedClicksChangeFraction' => 0.03,
								'predictedConversionsChangeFraction' => 0.01,
								'effectiveness'  => 'HIGH',
							],
						],
					],
				]
			);

		$results = $this->price_benchmarks->get_price_insights_data( [] );

		$this->assertSame(
			[
				[
					'id'                               => 'en~US~gla_29',
					'offer_id'                         => 'gla_29',
					'title'                            => 'Hoodie',
					'price_micros'                     => '45000000',
					'currency_code'                    => 'USD',
					'suggested_price_micros'           => '42000000',
					'suggested_price_currency_code'    => 'USD',
					'predicted_impressions_change_fraction' => 0.05,
					'predicted_clicks_change_fraction' => 0.03,
					'predicted_conversions_change_fraction' => 0.01,
					'effectiveness'                    => 'HIGH',
				],
			],
			$results
		);
	}

	public function test_get_merchant_performance_data() {
		$this->client->expects( $this->once() )
			->method( 'post' )
			->with(
				'reports/v1/accounts/432/reports:search',
				[
					'query' => "SELECT product_performance_view.offer_id,product_performance_view.clicks,product_performance_view.impressions,product_performance_view.click_through_rate,product_performance_view.conversions FROM product_performance_view WHERE product_performance_view.date BETWEEN '2026-01-05' AND '2026-01-11'",
				]
			)
			->willReturn(
				[
					'results' => [
						[
							'productPerformanceView' => [
								'offerId'          => 'gla_29',
								'clicks'           => '12',
								'impressions'      => '340',
								'clickThroughRate' => 0.035,
								'conversions'      => 2.0,
							],
						],
					],
				]
			);

		$results = $this->price_benchmarks->get_merchant_performance_data(
			[
				'after'  => '2026-01-05',
				'before' => '2026-01-11',
			]
		);

		$this->assertSame(
			[
				[
					'offer_id'    => 'gla_29',
					'clicks'      => '12',
					'impressions' => '340',
					'ctr'         => 0.035,
					'conversions' => 2.0,
				],
			],
			$results
		);
	}

	public function test_get_price_comparisons_data_returns_empty_array_when_no_results() {
		$this->client->method( 'post' )->willReturn( [] );

		$this->assertSame( [], $this->price_benchmarks->get_price_comparisons_data( [] ) );
	}

	public function test_get_price_comparisons_data_throws_exception_with_response_data() {
		$this->client->method( 'post' )
			->willThrowException(
				new MerchantApiException(
					403,
					[
						'error' => [
							'message' => 'Permission denied',
							'errors'  => [ [ 'reason' => 'forbidden' ] ],
						],
					],
					__METHOD__
				)
			);

		try {
			$this->price_benchmarks->get_price_comparisons_data( [] );
			$this->fail( 'Expected ExceptionWithResponseData to be thrown' );
		} catch ( ExceptionWithResponseData $e ) {
			$this->assertSame( 403, $e->getCode() );
			$this->assertSame( [ 'errors' => [ [ 'reason' => 'forbidden' ] ] ], $e->get_response_data() );
		}
	}

	public function test_get_price_insights_data_throws_exception_with_response_data() {
		$this->client->method( 'post' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 500 );

		$this->price_benchmarks->get_price_insights_data( [] );
	}

	public function test_get_merchant_performance_data_throws_exception_with_response_data() {
		$this->client->method( 'post' )
			->willThrowException( new MerchantApiException( 500, [], __METHOD__ ) );

		$this->expectException( ExceptionWithResponseData::class );
		$this->expectExceptionCode( 500 );

		$this->price_benchmarks->get_merchant_performance_data( [] );
	}
}
