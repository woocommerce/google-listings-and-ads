<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsRecommendationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\RecommendationsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WP_REST_Response as Response;

/**
 * Class RecommendationsControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class RecommendationsControllerTest extends RESTControllerUnitTest {

	/** @var MockObject|AccountService $account */
	protected $account;

	/** @var RecommendationsController $controller */
	protected $controller;

	/** @var MockObject|AdsRecommendationsService */
	protected $recommendations;

	/** @var Container */
	protected $container;

	protected const ROUTE_RECOMMENDATIONS = '/wc/gla/ads/recommendations';

	public function setUp(): void {
		parent::setUp();

		$this->recommendations = $this->createMock( AdsRecommendationsService::class );

		// Mock the container to return the mocked AdsRecommendationsService.
		$this->container = new Container();
		$this->container->addShared( AdsRecommendationsService::class, $this->recommendations );

		$this->account    = $this->createMock( AccountService::class );
		$this->controller = new RecommendationsController( $this->server, $this->account );
		$this->controller->set_container( $this->container );
		$this->controller->register();
	}

	public function test_get_recommendations_returns_empty_result() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET' );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $response->get_data()['code'] );
	}

	public function test_get_recommendations_returns_all_recommendations() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
		];

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( $mock_recommendations_data );

		$filter_by_type = [
			'types' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		];

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );
		$this->assertEquals( 1, $data[0]['id'] );
		$this->assertEquals( 2, $data[1]['id'] );
	}

	public function test_get_recommendations_returns_empty_array_when_filter_by_non_existent_type() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$filter_by_type = [
			'types' => 'NON_EXISTENT_TYPE',
		];

		// Filter by a type that does not exist in the stubbed recommendations.
		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	public function test_get_recommendations_returns_error_if_account_not_connected() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'not_connected' ] );

		$filter_by_type = [
			'types' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		];

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 403, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'No connected Ads account found.', $data['message'] );
	}

	public function test_get_recommendations_filter_by_type_returns_only_matching() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$filter_by_type = [
			'types' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		];

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 3,
				'type'            => 'CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/124/recommendations/3',
				'campaign_id'     => 103,
				'campaign_name'   => 'Another Campaign 03',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
		];

		// Only return the recommendation matching the filter.
		$filtered_mock_data = array_filter(
			$mock_recommendations_data,
			function ( $rec ) use ( $filter_by_type ) {
				return $rec['type'] === $filter_by_type['types'];
			}
		);

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( $filtered_mock_data );

		// Filter by a type that does not exist in the stubbed recommendations.
		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );
		foreach ( $data as $key => $rec ) {
			$this->assertEquals( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH', $rec['type'] );
		}
	}

	public function test_get_recommendations_filter_by_multiple_type_returns_only_matching() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$filter_by_type = [
			'types' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH, MARGINAL_ROI_CAMPAIGN_BUDGET',
		];

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'MARGINAL_ROI_CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 3,
				'type'            => 'CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/124/recommendations/3',
				'campaign_id'     => 103,
				'campaign_name'   => 'Another Campaign 03',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
		];

		// Only return the recommendation matching the filter.
		$types              = array_map( 'trim', explode( ',', $filter_by_type['types'] ) );
		$filtered_mock_data = array_filter(
			$mock_recommendations_data,
			function ( $rec ) use ( $types ) {
				return in_array( $rec['type'], $types, true );
			}
		);

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( $filtered_mock_data );

		// Filter by a type that does not exist in the stubbed recommendations.
		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );
		$this->assertSame( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH', $data[0]['type'] );
		$this->assertSame( 'MARGINAL_ROI_CAMPAIGN_BUDGET', $data[1]['type'] );
	}

	public function test_get_recommendations_filter_by_campaign_id_returns_single_recommendation() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
		];

		$filter_by_campaign_id = [
			'campaign_id' => 101,
			'types'       => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		];

		// Only return the recommendation matching the filter.
		$filtered_mock_data = array_filter(
			$mock_recommendations_data,
			function ( $rec ) use ( $filter_by_campaign_id ) {
				return $rec['campaign_id'] === $filter_by_campaign_id['campaign_id'];
			}
		);

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( array_values( $filtered_mock_data ) );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_campaign_id );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 1, $data );
		$this->assertEquals( 101, $data[0]['campaign_id'] );
	}

	public function test_get_recommendations_includes_correct_details_property() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'MARGINAL_ROI_CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'ENABLED',
				'details'         => [
					'campaign_budget_recommendation' => [
						'current_budget_amount'     => 20,
						'recommended_budget_amount' => 31,
						'budget_options'            => [
							[
								'budget_amount' => 20,
								'level'         => 'Current',
								'metrics'       => [
									'cost'              => 139.964209,
									'conversions'       => 4,
									'conversions_value' => 545.7408447265625,
								],
							],
							[
								'budget_amount' => 26,
								'level'         => 'Low',
								'metrics'       => [
									'cost'              => 181.971258,
									'conversions'       => 4.828944206237793,
									'conversions_value' => 622.0850219726562,
								],
							],
							[
								'budget_amount' => 31,
								'level'         => 'Recommended',
								'metrics'       => [
									'cost'              => 216961447,
									'conversions'       => 5.398608684539795,
									'conversions_value' => 679.2435913085938,
								],
							],
							[
								'budget_amount' => 36,
								'level'         => 'High',
								'metrics'       => [
									'cost'              => 251946304,
									'conversions'       => 5.776357173919678,
									'conversions_value' => 731.8743286132812,
								],
							],
						],
					],
				],
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 3,
				'type'            => 'CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/124/recommendations/3',
				'campaign_id'     => 102,
				'campaign_name'   => 'Another Campaign 02',
				'campaign_status' => 'ENABLED',
				'details'         => [
					'campaign_budget_recommendation' => [
						'current_budget_amount'     => 20,
						'recommended_budget_amount' => 31,
						'budget_options'            => [
							[
								'budget_amount' => 20,
								'level'         => 'Current',
								'metrics'       => [
									'cost'              => 139.964209,
									'conversions'       => 4,
									'conversions_value' => 545.7408447265625,
								],
							],
							[
								'budget_amount' => 26,
								'level'         => 'Low',
								'metrics'       => [
									'cost'              => 181.971258,
									'conversions'       => 4.828944206237793,
									'conversions_value' => 622.0850219726562,
								],
							],
							[
								'budget_amount' => 31,
								'level'         => 'Recommended',
								'metrics'       => [
									'cost'              => 216961447,
									'conversions'       => 5.398608684539795,
									'conversions_value' => 679.2435913085938,
								],
							],
							[
								'budget_amount' => 36,
								'level'         => 'High',
								'metrics'       => [
									'cost'              => 251946304,
									'conversions'       => 5.776357173919678,
									'conversions_value' => 731.8743286132812,
								],
							],
						],
					],
				],
				'last_synced'     => gmdate( 'c' ),
			],
		];

		$filter_types = [
			'types' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH, MARGINAL_ROI_CAMPAIGN_BUDGET, CAMPAIGN_BUDGET',
		];

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( array_values( $mock_recommendations_data ) );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_types );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 3, $data );

		$this->assertEquals( 100, $data[0]['campaign_id'] );
		$this->assertEquals( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH', $data[0]['type'] );
		$this->assertEmpty( $data[0]['details'] );

		$this->assertEquals( 101, $data[1]['campaign_id'] );
		$this->assertEquals( 'MARGINAL_ROI_CAMPAIGN_BUDGET', $data[1]['type'] );
		$this->assertIsArray( $data[1]['details'] );
		$this->assertIsArray( $data[1]['details']['campaign_budget_recommendation'] );
		$this->assertIsArray( $data[1]['details']['campaign_budget_recommendation']['budget_options'] );

		$this->assertEquals( 102, $data[2]['campaign_id'] );
		$this->assertEquals( 'CAMPAIGN_BUDGET', $data[2]['type'] );
		$this->assertIsArray( $data[2]['details'] );
		$this->assertIsArray( $data[2]['details']['campaign_budget_recommendation'] );
		$this->assertIsArray( $data[2]['details']['campaign_budget_recommendation']['budget_options'] );
	}
}
