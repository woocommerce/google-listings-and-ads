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
	private $middleware;

	public function setUp(): void {
		parent::setUp();
		$this->middleware = $this->createMock( Middleware::class );
		$this->controller = new RequestReviewController( $this->server, $this->middleware, new RequestReviewStatuses() );
		$this->controller->register();
	}

	public function test_route() {

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
			'status'   => 'WARNING',
			'issues'   => [ 'one', 'two' ],
			'cooldown' => 0
		], $response->get_data() );
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
			'status'   => 'DISAPPROVED',
			'issues'   => [ 'one', 'two' ],
			'cooldown' => 0
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
			'status'   => 'DISAPPROVED',
			'issues'   => [],
			'cooldown' => 1651047106000 // 27/04/2022
		], $response->get_data() );
	}

	public function test_exception_in_route() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willThrowException( new Exception( 'error', 401 ) );


		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_GET_REQUEST, $this->server->get_routes() );
	}

}
