<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
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
	private $transients;
	private $request_review_statuses;

	public function setUp(): void {
		parent::setUp();
		$this->middleware = $this->createMock( Middleware::class );
		$this->transients = $this->createMock( TransientsInterface::class );
		$this->request_review_statuses = new RequestReviewStatuses();
		$this->controller = new RequestReviewController( $this->server, $this->middleware, $this->request_review_statuses, $this->transients  );
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
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::WARNING,
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
			'status'   => RequestReviewStatuses::WARNING,
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
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
						                 ]
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								                 'reviewIssues'            => [ 'two' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::UNDER_REVIEW
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::DISAPPROVED,
			'issues'   => [ 'one', 'two' ],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_no_offers_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
						                 ]
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  RequestReviewStatuses::NO_OFFERS
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => 'ONBOARDING',
			'issues'   => [ 'one' ],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_state_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  "WEIRD"
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" =>  "UNKNOWN"
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => null,
			'issues'   => [],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_state_and_good_states_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" => "WEIRD"
					                 ]
				                 ],
				                 'programTypeB' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'INELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							                 ]
						                 ]
					                 ]
				                 ],
				                 'programTypeC' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState" => "UNKNOWN"
					                 ]
				                 ],
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::APPROVED,
			'issues'   => [],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unexpected_status_response() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'reviewEligibilityStatus' => 'UNKNOWN',
								                 'eligibilityStatus'       => 'UNKNOWN',
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'ELIGIBLE',
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								                 'reviewIssues'            => [ 'one' ]
							                 ],
							                 [
								                 'reviewEligibilityStatus' => 'WEIRD',
								                 'eligibilityStatus'       => 'WEIRD',
							                 ],
						                 ]
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => RequestReviewStatuses::DISAPPROVED,
			'issues'   => [ 'one' ],
			'cooldown' => 0
		], $response->get_data() );
	}

	public function test_unset_region_statuses() {

		$this->middleware->expects( $this->once() )
		                 ->method( 'get_account_review_status' )
		                 ->willReturn(
			                 [
				                 'programTypeA' => [
					                 'status' => 200,
					                 'data'   => [
						                 "globalState"    => RequestReviewStatuses::ENABLED,
					                 ]
				                 ]
			                 ]
		                 );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [
			'status'   => null,
			'issues'   => [],
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
						                 "globalState" =>  RequestReviewStatuses::ENABLED,
						                 'regionStatuses' => [
							                 [
								                 'regionCodes'                      => [ 'US' ],
								                 'eligibilityStatus'                => RequestReviewStatuses::DISAPPROVED,
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "2022-04-27T10:58:51Z" // 27/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'                      => [ 'NL' ],
								                 'eligibilityStatus'                => RequestReviewStatuses::DISAPPROVED,
								                 'reviewEligibilityStatus'          => 'INELIGIBLE',
								                 'reviewIneligibilityReasonDetails' => [
									                 'cooldownTime' => "2022-04-25T10:58:51Z" // 25/04/2022
								                 ]
							                 ],
							                 [
								                 'regionCodes'             => [ 'IT' ],
								                 'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
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
			'status'   => RequestReviewStatuses::DISAPPROVED,
			'issues'   => [],
			'cooldown' => 1651058331000 // 27/04/2022
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
