<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdvertisingSource;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
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
 * Class BudgetRecommendationsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class BudgetRecommendationsTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var MockObject|MerchantMetrics $merchant_metrics */
	protected $merchant_metrics;

	/** @var BudgetRecommendations $recommendations */
	protected $recommendations;

	protected const TEST_ADS_ID         = 1234567890;
	protected const TEST_MC_ID          = 9876543210;
	protected const TEST_RECOMMENDATION = [
		'daily_budget' => 12,
		'metrics'      => [
			'cost'              => 84,
			'conversions'       => 6.1,
			'conversions_value' => 243.88,
		],
		'country'      => 'US',
		'level'        => 'Recommended',
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

		$this->recommendations = new BudgetRecommendations( $this->client, $this->merchant_metrics );
		$this->recommendations->set_options_object( $this->options );
		$this->recommendations->set_transients_object( $this->transients );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
		$this->options->method( 'get_merchant_id' )->willReturn( self::TEST_MC_ID );
	}

	public function test_get_recommendations_cached() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$recommendations = [
			'us-new' => self::TEST_RECOMMENDATION,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS )
			->willReturn( $recommendations );

		$this->assertEquals( $recommendations['us-new'], $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_cache_key_uses_existing_for_advertisers_with_campaigns() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 3 );

		$recommendations = [
			'us-existing' => self::TEST_RECOMMENDATION,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS )
			->willReturn( $recommendations );

		$this->assertEquals( $recommendations['us-existing'], $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_sends_is_new_customer_true_when_no_campaigns() {
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

		$this->recommendations->get_recommendations( [ 'US' ] );

		$this->assertNotNull( $captured_request );
		$this->assertTrue( $captured_request->getIsNewCustomer() );
	}

	public function test_get_recommendations_sends_is_new_customer_false_when_existing_campaigns() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 5 );

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

		$this->recommendations->get_recommendations( [ 'US' ] );

		$this->assertNotNull( $captured_request );
		$this->assertFalse( $captured_request->getIsNewCustomer() );
	}

	public function test_get_recommendations_cached_locations() {
		$recommendations = [ self::TEST_RECOMMENDATION ];

		$invoked_count = $this->exactly( 2 );
		$this->transients->expects( $invoked_count )
			->method( 'get' )
			->willReturnCallback(
				function ( string $transient ) use ( $invoked_count ) {
					if ( 1 === $invoked_count->getInvocationCount() && $transient === TransientsInterface::ADS_BUDGET_RECOMMENDATIONS ) {
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

		$this->generate_recommendations_mock( $recommendations );

		$this->assertEquals( $recommendations, $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_empty_set() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock( [] );
		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_with_other_recommendation_type() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock( [ self::TEST_RECOMMENDATION ], true );
		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_exception() {
		$this->generate_location_ids_mock( [ 111 => 'US' ] );

		$this->generate_recommendations_mock_exception(
			new ApiException( 'failed', 7 )
		);

		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_get_recommendations_location_id_exception() {
		$this->generate_ads_query_mock_exception(
			new ApiException( 'failed location IDs', 8 )
		);
		$this->generate_recommendations_mock( [] );

		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}

	public function test_get_recommendations_with_zeros() {
		$recommendations = [
			[
				'daily_budget' => 0,
				'metrics'      => [
					'cost'              => 0,
					'conversions'       => 0,
					'conversions_value' => 0,
				],
			],
		];

		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock( $recommendations );

		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations() {
		$recommendations = [
			self::TEST_RECOMMENDATION,
			[
				'daily_budget' => 14.4,
				'metrics'      => [
					'cost'              => 100.8,
					'conversions'       => 6.5,
					'conversions_value' => 258.72,
				],
				'country'      => 'US',
				'level'        => 'High',
			],
			[
				'daily_budget' => 9.6,
				'metrics'      => [
					'cost'              => 67.2,
					'conversions'       => 5.7,
					'conversions_value' => 226.13,
				],
				'country'      => 'US',
				'level'        => 'Low',
			],
		];

		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock( $recommendations );

		$this->assertEquals( $recommendations, $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	/**
	 * Run the request and return it, so a test can inspect what was sent to Google Ads.
	 *
	 * @param string|null $advertising_source The advertising source to pass, or null to omit it.
	 */
	protected function capture_request( ?string $advertising_source ) {
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

		if ( null === $advertising_source ) {
			$this->recommendations->get_recommendations( [ 'US' ] );
		} else {
			$this->recommendations->get_recommendations( [ 'US' ], $advertising_source );
		}

		$this->assertNotNull( $captured_request );
		return $captured_request;
	}

	public function test_get_recommendations_omits_merchant_center_account_for_page() {
		$request = $this->capture_request( AdvertisingSource::PAGE );

		$this->assertEquals( 0, $request->getMerchantCenterAccountId() );
	}

	public function test_get_recommendations_includes_merchant_center_account_for_product() {
		$request = $this->capture_request( AdvertisingSource::PRODUCT );

		$this->assertEquals( self::TEST_MC_ID, $request->getMerchantCenterAccountId() );
	}

	public function test_get_recommendations_includes_merchant_center_account_without_advertising_source() {
		$request = $this->capture_request( null );

		$this->assertEquals( self::TEST_MC_ID, $request->getMerchantCenterAccountId() );
	}

	public function test_get_recommendations_cache_key_for_page_is_kept_apart_from_product() {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$cached = [
			'us-new'      => self::TEST_RECOMMENDATION,
			'us-new-page' => [ 'daily_budget' => 20 ],
		];

		$this->transients->method( 'get' )
			->with( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS )
			->willReturn( $cached );

		$this->assertEquals( $cached['us-new-page'], $this->recommendations->get_recommendations( [ 'US' ], AdvertisingSource::PAGE ) );
		$this->assertEquals( $cached['us-new'], $this->recommendations->get_recommendations( [ 'US' ], AdvertisingSource::PRODUCT ) );
		$this->assertEquals( $cached['us-new'], $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	/**
	 * Run a request with the given cached entries and return what was saved to the budget transient.
	 *
	 * @param string $advertising_source The advertising source to request.
	 * @param array  $cached             Entries already in the budget transient.
	 */
	protected function get_saved_budget_transient( string $advertising_source, array $cached ): ?array {
		$this->merchant_metrics->method( 'get_campaign_count' )->willReturn( 0 );

		$this->generate_location_ids_mock( [ 111 => 'US' ] );
		$this->generate_recommendations_mock( [ self::TEST_RECOMMENDATION ] );

		$this->transients->method( 'get' )->willReturnCallback(
			function ( string $name ) use ( $cached ) {
				return TransientsInterface::ADS_BUDGET_RECOMMENDATIONS === $name ? $cached : null;
			}
		);

		$saved = null;
		$this->transients->method( 'set' )->willReturnCallback(
			function ( string $name, $value ) use ( &$saved ) {
				if ( TransientsInterface::ADS_BUDGET_RECOMMENDATIONS === $name ) {
					$saved = $value;
				}
				return true;
			}
		);

		$this->recommendations->get_recommendations( [ 'US' ], $advertising_source );

		return $saved;
	}

	public function test_get_recommendations_saves_page_results_under_a_key_with_a_page_suffix_and_keeps_other_entries() {
		$saved = $this->get_saved_budget_transient( AdvertisingSource::PAGE, [ 'us-existing' => [ 'daily_budget' => 20 ] ] );

		$this->assertSame( [ 'us-existing', 'us-new-page' ], array_keys( $saved ) );
	}

	public function test_get_recommendations_saves_product_results_under_the_key_without_a_suffix() {
		$saved = $this->get_saved_budget_transient( AdvertisingSource::PRODUCT, [] );

		$this->assertSame( [ 'us-new' ], array_keys( $saved ) );
	}
}
