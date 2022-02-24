<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\BudgetRecommendationController;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\BudgetRecommendationQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class BudgetRecommendationControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 *
 * @property BudgetRecommendationQuery|MockObject $budget_recommendation_query
 * @property ISO3166DataProvider|MockObject       $iso_provider;
 * @property BudgetRecommendationController       $controller
 */
class BudgetRecommendationControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_BUDGET_RECOMMENDATION  = '/wc/gla/ads/campaigns/budget-recommendation';

	public function setUp() {
		parent::setUp();

		$this->budget_recommendation_query = $this->createMock( BudgetRecommendationQuery::class );
		$this->budget_recommendation_query->method( 'where' )
			->willReturn( $this->budget_recommendation_query );

		$this->iso_provider = $this->createMock( ISO3166DataProvider::class );
		$this->middleware = $this->createMock( Middleware::class );

		$this->controller = new BudgetRecommendationController( $this->server, $this->budget_recommendation_query, $this->middleware );
		$this->controller->register();
		$this->controller->set_iso3166_provider( $this->iso_provider );
	}

	public function test_get_budget_recommendation() {
		$budget_recommendation_params = [
			'country_codes' => ['JP', 'TW', 'GB', 'US'],
		];

		$budget_recommendation_data = [
			[
				'country'           => 'US',
				'daily_budget_low'  => '330',
				'daily_budget_high' => '930',
			],
			[
				'country'           => 'GB',
				'daily_budget_low'  => '245',
				'daily_budget_high' => '625',
			],
			[
				'country'           => 'TW',
				'daily_budget_low'  => '95',
				'daily_budget_high' => '255',
			],
			[
				'country'           => 'JP',
				'daily_budget_low'  => '110',
				'daily_budget_high' => '320',
			]
		];

		$expected_response_data = [
			'currency'        => 'TWD',
			'recommendations' => [
				[
					'country'           => 'US',
					'daily_budget_low'  => 330.0,
					'daily_budget_high' => 930.0,
				],
				[
					'country'           => 'GB',
					'daily_budget_low'  => 245.0,
					'daily_budget_high' => 625.0,
				],
				[
					'country'           => 'TW',
					'daily_budget_low'  => 95.0,
					'daily_budget_high' => 255.0,
				],
				[
					'country'           => 'JP',
					'daily_budget_low'  => 110.0,
					'daily_budget_high' => 320.0,
				]
			],
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->exactly(2) )->method( 'where' );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $budget_recommendation_data );

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

	public function test_get_budget_recommendation_with_nonexistent_country_code() {
		$budget_recommendation_params = [
			'country_codes' => ['AAAAA'],
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
			'country_codes' => ['JP', 'TW', 'GB', 'US'],
		];

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Invalid currency',
				'currency'      => '',
				'country_codes' => ['JP', 'TW', 'GB', 'US'],
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_cannot_find_any_recommendations() {
		$budget_recommendation_params = [
			'country_codes' => ['JP', 'TW', 'GB', 'US'],
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Cannot find any budget recommendations',
				'currency'      => 'TWD',
				'country_codes' => ['JP', 'TW', 'GB', 'US'],
			],
			$response->get_data()
		);
		$this->assertEquals( 404, $response->get_status() );
	}
}
