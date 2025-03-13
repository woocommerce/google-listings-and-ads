<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
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

	/** @var BudgetRecommendations $recommendations */
	protected $recommendations;

	protected const TEST_ADS_ID         = 1234567890;
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

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->transients = $this->createMock( TransientsInterface::class );

		$this->recommendations = new BudgetRecommendations( $this->client );
		$this->recommendations->set_options_object( $this->options );
		$this->recommendations->set_transients_object( $this->transients );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );
	}

	public function test_get_recommendations_cached() {
		$recommendations = [
			'us' => self::TEST_RECOMMENDATION,
		];

		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_RECOMMENDATIONS )
			->willReturn( $recommendations );

		$this->assertEquals( $recommendations['us'], $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_cached_locations() {
		$recommendations = [ self::TEST_RECOMMENDATION ];

		$invoked_count = $this->exactly( 2 );
		$this->transients->expects( $invoked_count )
			->method( 'get' )
			->willReturnCallback(
				function ( string $transient ) use ( $invoked_count ) {
					if ( 1 === $invoked_count->getInvocationCount() && $transient === TransientsInterface::ADS_RECOMMENDATIONS ) {
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
}
