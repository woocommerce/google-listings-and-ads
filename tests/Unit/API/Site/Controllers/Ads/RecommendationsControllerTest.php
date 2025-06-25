<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\RecommendationsController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\AccountReconnect;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
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

	protected const ROUTE_RECOMMENDATIONS = '/wc/gla/ads/recommendations';

	public function setUp(): void {
		parent::setUp();

		$this->account    = $this->createMock( AccountService::class );
		$this->controller = new RecommendationsController( $this->server, $this->account );
		$this->controller->register();
	}

	public function test_get_recommendations_returns_all_recommendations() {
		$this->account->method( 'get_connected_account' )
			->willReturn( [ 'status' => 'connected' ] );

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

		$filter_by_id = [
			'id' => 2,
		];

		// Filter by a type that does not exist in the stubbed recommendations.
		$response = $this->do_request( self::ROUTE_RECOMMENDATIONS, 'GET', $filter_by_id );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 1, $data );
		$this->assertInstanceOf( Response::class, $data[0] );
		$this->assertEquals( 2, $data[0]->get_data()['id'] );
	}
}
