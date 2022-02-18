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
		$this->iso_provider = $this->createMock( ISO3166DataProvider::class );
		$this->middleware = $this->createMock( Middleware::class );

		$this->controller = new BudgetRecommendationController( $this->server, $this->budget_recommendation_query, $this->middleware );
		$this->controller->register();
		$this->controller->set_iso3166_provider( $this->iso_provider );
	}

	public function test_get_budget_recommendation() {
		$budget_recommendation_params = [
			'country_codes' => 'JP,TW,GB,US',
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
			'currency'          => 'TWD',
			'country_codes'     => ['US', 'GB', 'TW', 'JP'],
			'daily_budget_low'  => [330, 245, 95, 110],
			'daily_budget_high' => [930, 625, 255, 320],
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->exactly(2) )
			->method( 'where' )
			->willReturn( $this->budget_recommendation_query );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $budget_recommendation_data );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals( $expected_response_data, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_budget_recommendation_without_query_parameters() {
		$budget_recommendation_params = [];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Invalid country_codes/currency combination',
				'currency'      => 'TWD',
				'country_codes' => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_wrong_query_parameters() {
		$budget_recommendation_params = [
			'country_codes' => '',
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Invalid country_codes/currency combination',
				'currency'      => 'TWD',
				'country_codes' => '',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_with_nonexistent_country_code() {
		$budget_recommendation_params = [
			'country_codes' => 'AAAAA',
		];

		$this->middleware->expects( $this->once() )
			->method( 'get_ads_currency' )
			->willReturn( 'TWD' );

		$this->budget_recommendation_query->expects( $this->exactly(2) )
			->method( 'where' )
			->willReturn( $this->budget_recommendation_query );

		$this->budget_recommendation_query->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( null );

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Cannot find any budget recommendations',
				'currency'      => 'TWD',
				'country_codes' => 'AAAAA',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_get_budget_recommendation_without_currency() {
		$budget_recommendation_params = [
			'country_codes' => 'JP,TW,GB,US',
		];

		$response = $this->do_request( self::ROUTE_BUDGET_RECOMMENDATION, 'GET', $budget_recommendation_params );

		$this->assertEquals(
			[
				'message'       => 'Invalid country_codes/currency combination',
				'currency'      => '',
				'country_codes' => 'JP,TW,GB,US',
			],
			$response->get_data()
		);
		$this->assertEquals( 400, $response->get_status() );
	}
}
