<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Exception as GoogleException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Http\Message\ResponseInterface;

/**
 * Test suite for RequestReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 */
class RequestReviewControllerTest extends RESTControllerUnitTest {


	protected const ROUTE_REQUEST = '/wc/gla/mc/review';
	private $middleware;
	private $merchant;
	private $transients;
	private $request_review_statuses;
	private $response_interface;


	protected const APPROVED_REGION = [
		'regionCodes'             => [ 'US', 'CA' ],
		'reviewEligibilityStatus' => 'INELIGIBLE',
		'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
	];

	protected const DISAPPROVED_REGION = [
		'regionCodes'             => [ 'US' ],
		'reviewEligibilityStatus' => 'ELIGIBLE',
		'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
		'reviewIssues'            => [ 'one', 'two' ],
	];

	protected const DISAPPROVED_WITH_COOLDOWN_REGION = [
		'regionCodes'                      => [ 'US' ],
		'eligibilityStatus'                => RequestReviewStatuses::DISAPPROVED,
		'reviewEligibilityStatus'          => 'INELIGIBLE',
		'reviewIneligibilityReasonDetails' => [
			'cooldownTime' => '2022-04-27T10:58:51Z', // 27/04/2022
		],
	];

	protected const BAD_FORMAT = [
		'globalState' => 'UNKNOWN',
	];

	public function setUp(): void {
		parent::setUp();
		$this->middleware = $this->createMock( Middleware::class );
		$this->middleware->method( 'is_subaccount' )->willReturn( true );

		$this->merchant                = $this->createMock( Merchant::class );
		$this->transients              = $this->createMock( TransientsInterface::class );
		$this->request_review_statuses = new RequestReviewStatuses();
		$this->controller              = new RequestReviewController( $this->server, $this->middleware, $this->merchant, $this->request_review_statuses, $this->transients );
		$this->controller->register();
	}


	public function test_get_status_route() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								'reviewIssues'            => [ 'one', 'two' ],
							],
						],
					],
					'shoppingAdsProgram'  => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								'reviewIssues'            => [ 'one', 'two' ],
							],
						],
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::WARNING,
				'issues'                => [ 'one', 'two' ],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_request_review_route() {
		$this->createGoogleResponseInterfaceMock( 200 );
		$this->merchant->expects( $this->once() )
			->method( 'account_request_review' )
			->willReturn( $this->response_interface );

		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::DISAPPROVED_REGION,
						],
					],
				]
			);

		$response = $this->do_post_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'issues'                => [],
				'cooldown'              => 0,
				'status'                => $this->request_review_statuses::UNDER_REVIEW,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_request_review_route_in_cooldown() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::DISAPPROVED_WITH_COOLDOWN_REGION,
						],
					],
				]
			);

		$response = $this->do_post_request_review();
		$this->assertEquals( 'Your account is under cool down period and cannot request a new review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_request_review_route_ineligible() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::APPROVED_REGION,
						],
					],
				]
			);

		$response = $this->do_post_request_review();
		$this->assertEquals( 'Your account is not eligible for a new request review.', $response->get_data()['message'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_merged_response() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::APPROVED_REGION,
							self::DISAPPROVED_REGION,
						],
					],
					'shoppingAdsProgram'  => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'eligibilityStatus'       => RequestReviewStatuses::WARNING,
								'reviewIssues'            => [ 'two' ],
							],
							self::DISAPPROVED_REGION,
							[
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'eligibilityStatus'       => RequestReviewStatuses::UNDER_REVIEW,
							],
						],
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::DISAPPROVED,
				'issues'                => [ 'one', 'two' ],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [ 'US' => [ 'freelistingsprogram', 'shoppingadsprogram' ] ],
			],
			$response->get_data()
		);
	}

	public function test_unexpected_state_response() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => self::BAD_FORMAT,
					'shoppingAdsProgram'  => self::BAD_FORMAT,
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => null,
				'issues'                => [],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_unexpected_state_and_good_states_response() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => self::BAD_FORMAT,
					'shoppingAdsProgram'  => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::APPROVED_REGION,
						],
					],
					'programTypeC'        => self::BAD_FORMAT,
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::APPROVED,
				'issues'                => [],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_unexpected_status_response() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'regionCodes'             => [ 'US', 'CA' ],
								'reviewEligibilityStatus' => 'UNKNOWN',
								'eligibilityStatus'       => 'UNKNOWN',
							],
							self::DISAPPROVED_REGION,
							[
								'regionCodes'             => [ 'US', 'CA' ],
								'reviewEligibilityStatus' => 'WEIRD',
								'eligibilityStatus'       => 'WEIRD',
							],
						],
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::DISAPPROVED,
				'issues'                => [ 'one', 'two' ],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [ 'US' => [ 'freelistingsprogram' ] ],
			],
			$response->get_data()
		);
	}

	public function test_unset_region_statuses() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState' => RequestReviewStatuses::ENABLED,
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => null,
				'issues'                => [],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_cooldown() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'regionCodes'             => [ 'US' ],
								'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'reviewIneligibilityReasonDetails' => [
									'cooldownTime' => '2022-04-27T10:58:51Z', // 27/04/2022
								],
							],
							[
								'regionCodes'             => [ 'NL' ],
								'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'reviewIneligibilityReasonDetails' => [
									'cooldownTime' => '2022-04-25T10:58:51Z', // 25/04/2022
								],
							],
							self::DISAPPROVED_REGION,
						],
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::DISAPPROVED,
				'issues'                => [ 'one', 'two' ],
				'cooldown'              => 1651058331000, // 27/04/2022
				'reviewEligibleRegions' => [ 'US' => [ 'freelistingsprogram' ] ],
			],
			$response->get_data()
		);
	}


	public function test_no_offers_response() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							[
								'reviewEligibilityStatus' => 'INELIGIBLE',
								'eligibilityStatus'       => RequestReviewStatuses::APPROVED,
							],
							[
								'reviewEligibilityStatus' => RequestReviewStatuses::ELIGIBLE,
								'eligibilityStatus'       => RequestReviewStatuses::DISAPPROVED,
								'reviewIssues'            => [ 'one' ],
							],
						],
					],
					'shoppingAdsProgram'  => [
						'globalState' => RequestReviewStatuses::NO_OFFERS,
					],
				]
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'                => RequestReviewStatuses::ONBOARDING,
				'issues'                => [ 'one' ],
				'cooldown'              => 0,
				'reviewEligibleRegions' => [],
			],
			$response->get_data()
		);
	}

	public function test_exception_in_status_route() {
		$this->merchant->expects( $this->exactly( 2 ) )
			->method( 'get_account_review_status' )
			->willThrowException( new GoogleException( 'error', 401 ) );

		$responses = [ $this->do_post_request_review(), $this->do_get_request_review() ];
		foreach ( $responses as $response ) {
			$this->assertEquals( 'error', $response->get_data()['message'] );
			$this->assertEquals( 401, $response->get_status() );
		}
	}

	public function test_exception_in_request_route() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				[
					'freeListingsProgram' => [
						'globalState'    => RequestReviewStatuses::ENABLED,
						'regionStatuses' => [
							self::DISAPPROVED_REGION,
						],
					],
				]
			);

		$this->merchant->expects( $this->once() )
			->method( 'account_request_review' )
			->willThrowException( new GoogleException( 'error', 401 ) );

		$response = $this->do_post_request_review();
		$this->assertEquals( 'error', $response->get_data()['message'] );
		$this->assertEquals( 401, $response->get_status() );
	}

	private function do_post_request_review() {
		return $this->do_request( self::ROUTE_REQUEST, 'POST' );
	}

	private function do_get_request_review() {
		return $this->do_request( self::ROUTE_REQUEST );
	}

	private function createGoogleResponseInterfaceMock( $status ) {
		$this->response_interface = $this->createMock( ResponseInterface::class );
		$this->response_interface->expects( $this->once() )
			->method( 'getStatusCode' )
			->willReturn( $status );
	}
}
