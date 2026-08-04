<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantMetrics;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V23\Services\GenerateRecommendationsResponse;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetMetricsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class BudgetMetricsTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var MockObject|MerchantMetrics $merchant_metrics */
	protected $merchant_metrics;

	/** @var BudgetMetrics $metrics */
	protected $metrics;

	protected const TEST_ADS_ID  = 1234567890;
	protected const TEST_MC_ID   = 9876543210;
	protected const TEST_METRICS = [
		'cost'              => 84,
		'conversions'       => 6.1,
		'conversions_value' => 243.88,
	];

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options          = $this->createMock( OptionsInterface::class );
		$this->transients       = $this->createMock( TransientsInterface::class );
		$this->merchant_metrics = $this->createMock( MerchantMetrics::class );

		$this->metrics = new BudgetMetrics( $this->client, $this->merchant_metrics );
		$this->metrics->set_options_object( $this->options );
		$this->metrics->set_transients_object( $this->transients );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MC_ID );
	}

	public function test_get_metrics_cached() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$metrics = [
			'us-12-new' => self::TEST_METRICS,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_BUDGET_METRICS )
			->willReturn( $metrics );

		$this->assertEquals( $metrics['us-12-new'], $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}

	public function test_get_metrics_cache_key_uses_existing_for_advertisers_with_campaigns() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 4 );

		$metrics = [
			'us-12-existing' => self::TEST_METRICS,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_BUDGET_METRICS )
			->willReturn( $metrics );

		$this->assertEquals( $metrics['us-12-existing'], $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}

	public function test_get_metrics_sends_is_new_customer_true_when_no_campaigns() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$captured_request = null;
		$this->recommendation_service->method( 'generateRecommendations' )
			->willReturnCallback(
				function ( $request ) use ( &$captured_request ) {
					$captured_request = $request;
					$response         = $this->createMock( GenerateRecommendationsResponse::class );
					$response->method( 'getRecommendations' )->willReturn( [] );
					return $response;
				}
			);

		$this->metrics->get_metrics( 12, [ 'US' ] );

		$this->assertNotNull( $captured_request );
		$this->assertTrue( $captured_request->getIsNewCustomer() );
	}

	public function test_get_metrics_sends_is_new_customer_false_when_existing_campaigns() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 7 );

		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$captured_request = null;
		$this->recommendation_service->method( 'generateRecommendations' )
			->willReturnCallback(
				function ( $request ) use ( &$captured_request ) {
					$captured_request = $request;
					$response         = $this->createMock( GenerateRecommendationsResponse::class );
					$response->method( 'getRecommendations' )->willReturn( [] );
					return $response;
				}
			);

		$this->metrics->get_metrics( 12, [ 'US' ] );

		$this->assertNotNull( $captured_request );
		$this->assertFalse( $captured_request->getIsNewCustomer() );
	}

	public function test_get_metrics_cached_locations() {
		$metrics = [ self::TEST_METRICS ];

		$invoked_count = $this->exactly( 2 );
		$this->transients->expects( $invoked_count )
			->method( 'get' )
			->willReturnCallback(
				function ( string $transient ) use ( $invoked_count ) {
					if ( 1 === $invoked_count->getInvocationCount() && $transient === TransientsInterface::ADS_BUDGET_METRICS ) {
						return false;
					}

					if ( 2 === $invoked_count->getInvocationCount() && $transient === TransientsInterface::ADS_LOCATION_IDS ) {
						return [
							'us' => [
								111 => 'US',
							],
						];
					}
				}
			);

		$this->generate_recommendations_mock(
			[
				[
					'daily_budget' => 12.5,
					'metrics'      => self::TEST_METRICS,
				],
			]
		);

		$this->assertEquals( self::TEST_METRICS, $this->metrics->get_metrics( 12.5, [ 'US' ] ) );
	}

	public function test_get_metrics_empty_set() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock( [] );
		$this->assertNull( $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}

	public function test_get_metrics_with_other_recommendation_type() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock( [ self::TEST_METRICS ], true );
		$this->assertNull( $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}

	public function test_get_metrics_exception() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock_exception(
			new ApiException( 'failed', 7 )
		);

		$this->assertNull( $this->metrics->get_metrics( 12, [ 'US' ] ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_get_metrics_location_id_exception() {
		$this->generate_ads_query_mock_exception(
			new ApiException( 'failed location IDs', 8 )
		);
		$this->generate_recommendations_mock( [] );

		$this->assertNull( $this->metrics->get_metrics( 12, [ 'US' ] ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_get_metrics_with_different_budget() {
		$metrics = [
			[
				'daily_budget' => 5,
				'metrics'      => [
					'cost'              => 35,
					'conversions'       => 10,
					'conversions_value' => 48.12,
				],
			],
		];

		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock( $metrics );

		$this->assertNull( $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}

	public function test_get_metrics_with_float_inaccuracies() {
		$metrics = [
			[
				'daily_budget' => 16.3999998,
				'metrics'      => [
					'cost'              => 114.799986,
					'conversions'       => 6.7,
					'conversions_value' => 267.87,
				],
			],
		];

		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock( $metrics );

		$this->assertEquals( $metrics[0]['metrics'], $this->metrics->get_metrics( 16.4, [ 'US' ] ) );
	}

	public function test_get_metrics() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock(
			[
				[
					'daily_budget' => 12,
					'metrics'      => self::TEST_METRICS,
				],
			]
		);

		$this->assertEquals( self::TEST_METRICS, $this->metrics->get_metrics( 12, [ 'US' ] ) );
	}
}
