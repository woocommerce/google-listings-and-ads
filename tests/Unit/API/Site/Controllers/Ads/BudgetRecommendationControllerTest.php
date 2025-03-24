<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\BudgetRecommendationController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class BudgetRecommendationControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class BudgetRecommendationControllerTest extends RESTControllerUnitTest {

	/** @var Container $container */
	protected $container;

	/** @var MockObject|Ads $ads */
	protected $ads;

	/** @var MockObject|BudgetRecommendationQuery $budget_recommendation_query */
	protected $budget_recommendation_query;

	/** @var MockObject|BudgetRecommendations $budget_recommendations */
	protected $budget_recommendations;

	/** @var MockObject|ISO3166DataProvider $iso_provider */
	protected $iso_provider;

	/** @var BudgetRecommendationController $controller */
	protected $controller;

	protected const ROUTE_BUDGET_RECOMMENDATION = '/wc/gla/ads/campaigns/budget-recommendation';
	protected const RECOMMENDATION_DATA         = [
		[
			'daily_budget' => 12,
			'metrics'      => [
				'cost'              => 84,
				'conversions'       => 6.1,
				'conversions_value' => 243.88,
			],
			'country'      => 'GB',
			'level'        => 'Recommended',
		],
		[
			'daily_budget' => 14.4,
			'metrics'      => [
				'cost'              => 100.8,
				'conversions'       => 6.5,
				'conversions_value' => 258.72,
			],
			'country'      => 'GB',
			'level'        => 'High',
		],
		[
			'daily_budget' => 9.6,
			'metrics'      => [
				'cost'              => 67.2,
				'conversions'       => 5.7,
				'conversions_value' => 226.13,
			],
			'country'      => 'GB',
			'level'        => 'Low',
		],
	];

	public function setUp(): void {
		parent::setUp();

		$this->budget_recommendation_query = $this->createMock( BudgetRecommendationQuery::class );
		$this->budget_recommendation_query->method( 'where' )
			->willReturn( $this->budget_recommendation_query );

		$this->iso_provider           = $this->createMock( ISO3166DataProvider::class );
		$this->ads                    = $this->createMock( Ads::class );
		$this->budget_recommendations = $this->createMock( BudgetRecommendations::class );

		$this->container = new Container();
		$this->container->addShared( BudgetRecommendationQuery::class, $this->budget_recommendation_query );
		$this->container->addShared( BudgetRecommendations::class, $this->budget_recommendations );

		$this->controller = new BudgetRecommendationController( $this->server, $this->ads );
		$this->controller->register();
		$this->controller->set_container( $this->container );
		$this->controller->set_iso3166_provider( $this->iso_provider );
	}

	public function test_get_budget_recommendation() {
		$budget_recommendation_params = [
			'country_codes' => [ 'TW', 'GB', 'US' ],
		];

		$budget_recommendation_data = [
			[
				'country'      => 'TW',
				'daily_budget' => '330',
			],
		];

		$expected_response_data = [
			'currency'        => 'TWD',
			'recommendations' => [
				[
					'daily_budget' => 330.0,
					'country'      => 'TW',
					'level'        => 'Recommended',
				],
			],
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $budget_recommendation_data );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_metrics() {
		$budget_recommendation_params = [
			'country_codes' => [ 'GB', 'US' ],
		];

		$expected_response_data = [
			'currency'        => 'GBP',
			'recommendations' => self::RECOMMENDATION_DATA,
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'GBP' );

		$this->budget_recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( self::RECOMMENDATION_DATA );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_fallback_higher() {
		$budget_recommendation_params = [
			'country_codes' => [ 'GB', 'US' ],
		];

		$fallback_data = [
			[
				'daily_budget' => '15',
				'country'      => 'GB',
				'level'        => 'Recommended',
			],
		];

		$expected_response_data = [
			'currency'        => 'GBP',
			'recommendations' => [
				[
					'daily_budget' => 15.0,
					'country'      => 'GB',
					'level'        => 'Recommended',
				],
			],
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'GBP' );

		$this->budget_recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( self::RECOMMENDATION_DATA );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $fallback_data );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_fallback_lower_than_highest_recommendation() {
		$budget_recommendation_params = [
			'country_codes' => [ 'GB', 'US' ],
		];

		$fallback_data = [
			[
				'daily_budget' => '13.2',
				'country'      => 'GB',
				'level'        => 'Recommended',
			],
		];

		$expected_response_data = [
			'currency'        => 'GBP',
			'recommendations' => [
				[
					'daily_budget' => 13.2,
					'country'      => 'GB',
					'level'        => 'Recommended',
				],
				[
					'daily_budget' => 14.4,
					'metrics'      => [
						'cost'              => 100.8,
						'conversions'       => 6.5,
						'conversions_value' => 258.72,
					],
					'country'      => 'GB',
					'level'        => 'High',
				],
			],
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'GBP' );

		$this->budget_recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( self::RECOMMENDATION_DATA );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $fallback_data );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertSame( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_recommendation_without_query_parameters() {
		$budget_recommendation_params = [];

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
		$this->assertEquals( 'Missing parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test a failed query of budget recommendation with empty country codes.
	 */
	public function test_get_budget_recommendation_with_empty_country_codes() {
		$budget_recommendation_params = [
			'country_codes' => [],
		];

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_nonexistent_country_code() {
		$budget_recommendation_params = [
			'country_codes' => [ 'AAAAA' ],
		];

		$this->iso_provider
			->method( 'alpha2' )
			->willThrowException( new Exception( 'invalid_country' ) );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertEquals( 'Invalid parameter(s): country_codes', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_without_currency() {
		$budget_recommendation_params = [
			'country_codes' => [ 'JP', 'TW', 'GB', 'US' ],
		];

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'No currency available for the Ads account.',
				'currency'      => '',
				'country_codes' => [ 'JP', 'TW', 'GB', 'US' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_cannot_find_any_recommendations() {
		$budget_recommendation_params = [
			'country_codes' => [ 'JP', 'TW', 'GB', 'US' ],
		];

		$this->ads->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Cannot find any budget recommendations.',
				'currency'      => 'TWD',
				'country_codes' => [ 'JP', 'TW', 'GB', 'US' ],
			],
			$response->get_data()
		);
		$this->assertEquals( 404, $response->get_status() );
	}
}
