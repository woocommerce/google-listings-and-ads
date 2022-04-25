<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Google\Exception;

/**
 * Test suit for RequestReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 */
class RequestReviewControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_GET_REQUEST = '/wc/gla/mc/review';
	protected const ROUTE_REQUEST_REVIEW = '/wc/gla/mc/request-review';
	private $middleware;

	public function setUp(): void {
		parent::setUp();
		$this->middleware = $this->createMock( Middleware::class );
		$this->controller = new RequestReviewController( $this->server, $this->middleware, new RequestReviewStatuses() );
		$this->controller->register();
	}

	public function test_get_status_route() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programType' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => 'WARNING',
								                 'reviewIssues'            => [ 'one', 'two' ],
							                 ]
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'                => 'WARNING',
			'issues'                => [ 'one', 'two' ],
			'cooldown'              => 0,
			'reviewEligibleRegions' => []
		], $response->get_data() );
	}

	public function test_request_review_route() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'account_request_review' )
		                 ->willReturn(
			                 [
				                 'message' => 'A new review has been successfully requested'
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'message' => 'A new review has been successfully requested'
		], $response->get_data() );
	}

	public function test_request_review_route_in_cooldown() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "1651047106000" // 27/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'                      => [ 'MX' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'ELIGIBLE'
							                 ]
						                 ],
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 'Your account is under cool down period and cannot request a new review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_request_review_route_ineligible() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
							                 ]
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_REQUEST_REVIEW );
		$this->assertEquals( 'Your account is not eligible for a new request review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_merged_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => 'APPROVED',
							                 ],
							                 [
								                 'regionCodes'             => [ 'US', 'CA' ],
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => 'DISAPPROVED',
								                 'reviewIssues'            => [ 'one' ]
							                 ],
						                 ]
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => 'WARNING',
								                 'reviewIssues'            => [ 'two' ]
							                 ],
							                 [
								                 'regionCodes'             => [ 'US', 'CA' ],
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => 'DISAPPROVED',
								                 'reviewIssues'            => [ 'one' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => 'UNDER_REVIEW'
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'                => 'DISAPPROVED',
			'issues'                => [ 'one', 'two' ],
			'cooldown'              => 0,
			'reviewEligibleRegions' => [ 'US' ]
		], $response->get_data() );
	}

	public function test_cooldown() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'freeListingsProgram' => [
					                 'status' => 200,
					                 'data'   => [
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "1651047106000" // 27/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'                      => [ 'NL' ],
								                 'eligibilityStatus'                => 'DISAPPROVED',
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "1650875865000" // 25/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'             => [ 'IT' ],
								                 'eligibilityStatus'       => 'DISAPPROVED',
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'                => 'DISAPPROVED',
			'issues'                => [],
			'cooldown'              => 1651047106000, // 27/04/2022
			'reviewEligibleRegions' => [ 'IT' ]
		], $response->get_data() );
	}

	public function test_exception_in_routes() {
		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willThrowException( new Exception( 'error', 401 ) );

		$this->middleware->expects( $this->once() )
		                 ->method( 'account_request_review' )
		                 ->willThrowException( new Exception( 'error', 401 ) );


		$routes = [ self::ROUTE_GET_REQUEST, self::ROUTE_REQUEST_REVIEW ];

		foreach ( $routes as $route ) {
			$response = $this->do_request( $route );
			$this->assertEquals( 'error', $response->get_data()['message'] );
			$this->assertEquals( 401, $response->get_status() );
		}
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_GET_REQUEST, $this->server->get_routes() );
		$this->assertArrayHasKey( self::ROUTE_REQUEST_REVIEW, $this->server->get_routes() );
	}

}
