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
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'last_synced'     => gmdate( 'c' ),
			],
		];

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( $mock_recommendations_data );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );
		$this->assertInstanceOf( Response::class, $data[0] );
		$this->assertInstanceOf( Response::class, $data[1] );
		$this->assertEquals( 1, $data[0]->get_data()['id'] );
		$this->assertEquals( 2, $data[1]->get_data()['id'] );
	}

	public function test_get_recommendations_returns_empty_array_when_no_recommendations() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$filter_by_type = [
			'type' => 'NON_EXISTENT_TYPE',
		];

		// Filter by a type that does not exist in the stubbed recommendations.
		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_type );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	public function test_get_recommendations_returns_error_if_account_not_connected() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'not_connected' ] );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET' );

		$this->assertEquals( 403, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'No connected Ads account found.', $data['message'] );
	}

	public function test_get_recommendations_filter_by_type_returns_only_matching() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

		$filter_by_type = [
			'type' => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
		];

		$mock_recommendations_data = [
			[
				'id'              => 1,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/1',
				'campaign_id'     => 100,
				'campaign_name'   => 'Test Campaign',
				'campaign_status' => 'ENABLED',
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 3,
				'type'            => 'CAMPAIGN_BUDGET',
				'resource_name'   => 'customers/124/recommendations/3',
				'campaign_id'     => 103,
				'campaign_name'   => 'Another Campaign 03',
				'campaign_status' => 'PAUSED',
				'last_synced'     => gmdate( 'c' ),
			],
		];

		// Only return the recommendation matching the filter.
		$filtered_mock_data = array_filter(
			$mock_recommendations_data,
			function ( $rec ) use ( $filter_by_type ) {
				return $rec['type'] === $filter_by_type['type'];
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
			$this->assertInstanceOf( Response::class, $data[ $key ] );
			$this->assertEquals( 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH', $rec->get_data()['type'] );
		}
	}

	public function test_get_recommendations_filter_by_id_returns_single_recommendation() {
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
				'last_synced'     => gmdate( 'c' ),
			],
			[
				'id'              => 2,
				'type'            => 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH',
				'resource_name'   => 'customers/123/recommendations/2',
				'campaign_id'     => 101,
				'campaign_name'   => 'Another Campaign',
				'campaign_status' => 'PAUSED',
				'last_synced'     => gmdate( 'c' ),
			],
		];

		$filter_by_id = [
			'id' => 2,
		];

		// Only return the recommendation matching the filter.
		$filtered_mock_data = array_filter(
			$mock_recommendations_data,
			function ( $rec ) use ( $filter_by_id ) {
				return $rec['id'] === $filter_by_id['id'];
			}
		);

		$this->recommendations->expects( $this->once() )
			->method( 'get_recommendations' )
			->willReturn( array_values( $filtered_mock_data ) );

		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_id );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 1, $data );
		$this->assertInstanceOf( Response::class, $data[0] );
		$this->assertEquals( 2, $data[0]->get_data()['id'] );
	}
}
