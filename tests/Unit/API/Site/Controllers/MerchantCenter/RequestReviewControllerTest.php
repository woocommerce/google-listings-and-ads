<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\RequestReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;

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

	/**
	 * Build an in-app account-review action as it appears in a rendered issue.
	 *
	 * @param bool $available Whether the action is available.
	 *
	 * @return array
	 */
	private function in_app_action( bool $available = true ): array {
		return [
			'isAvailable'            => $available,
			'buttonLabel'            => 'Request review',
			'builtinUserInputAction' => [
				'actionContext' => 'ctx-token',
				'flows'         => [ [ 'id' => 'flow-1' ] ],
			],
		];
	}

	/**
	 * Build a redirect account-review action as it appears in a rendered issue.
	 *
	 * @param bool $available Whether the action is available.
	 *
	 * @return array
	 */
	private function redirect_action( bool $available = true ): array {
		return [
			'isAvailable'    => $available,
			'buttonLabel'    => 'Request review',
			'externalAction' => [
				'type' => RequestReviewStatuses::EXTERNAL_REVIEW_ACTION,
				'uri'  => 'https://merchants.google.com/review',
			],
		];
	}

	/**
	 * Build a `renderaccountissues` response wrapping a single issue.
	 *
	 * @param string $severity Issue impact severity.
	 * @param string $title    Issue title.
	 * @param array  $actions  Actions attached to the issue.
	 *
	 * @return array
	 */
	private function render_response( string $severity, string $title, array $actions = [] ): array {
		return [
			'renderedIssues' => [
				[
					'title'   => $title,
					'impact'  => [ 'severity' => $severity ],
					'actions' => $actions,
				],
			],
		];
	}

	public function test_get_status_route() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->in_app_action() ]
				)
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'       => RequestReviewStatuses::DISAPPROVED,
				'issues'       => [ 'Account suspended' ],
				'reviewAction' => [
					'type'          => 'in_app',
					'isAvailable'   => true,
					'buttonLabel'   => 'Request review',
					'actionContext' => 'ctx-token',
					'flowId'        => 'flow-1',
				],
			],
			$response->get_data()
		);
	}

	public function test_get_status_warning() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response( RequestReviewStatuses::SEVERITY_WARNING, 'Limited visibility' )
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'       => RequestReviewStatuses::WARNING,
				'issues'       => [ 'Limited visibility' ],
				'reviewAction' => null,
			],
			$response->get_data()
		);
	}

	public function test_get_status_approved_when_no_issues() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn( [ 'renderedIssues' => [] ] );

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'       => RequestReviewStatuses::APPROVED,
				'issues'       => [],
				'reviewAction' => null,
			],
			$response->get_data()
		);
	}

	public function test_get_redirect_action() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->redirect_action() ]
				)
			);

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'type'        => 'redirect',
				'isAvailable' => true,
				'buttonLabel' => 'Request review',
				'uri'         => 'https://merchants.google.com/review',
			],
			$response->get_data()['reviewAction']
		);
	}

	public function test_get_status_uses_cache() {
		$cached = [
			'status'       => RequestReviewStatuses::WARNING,
			'issues'       => [ 'cached issue' ],
			'reviewAction' => null,
		];
		$this->transients->method( 'get' )->willReturn( $cached );

		$this->merchant->expects( $this->never() )->method( 'get_account_review_status' );

		$response = $this->do_get_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $cached, $response->get_data() );
	}

	public function test_request_review_in_app() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->in_app_action() ]
				)
			);

		$this->merchant->expects( $this->once() )
			->method( 'trigger_review_action' )
			->with( 'ctx-token', 'flow-1' )
			->willReturn( [ 'name' => 'accounts/12345/action' ] );

		$response = $this->do_post_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			[
				'status'       => RequestReviewStatuses::UNDER_REVIEW,
				'issues'       => [],
				'reviewAction' => null,
			],
			$response->get_data()
		);
	}

	public function test_request_review_ineligible() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->in_app_action( false ) ]
				)
			);

		$this->merchant->expects( $this->never() )->method( 'trigger_review_action' );

		$response = $this->do_post_request_review();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Your account is not eligible for a new review request.', $response->get_data()['message'] );
	}

	public function test_request_review_redirect_must_use_merchant_center() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->redirect_action() ]
				)
			);

		$this->merchant->expects( $this->never() )->method( 'trigger_review_action' );

		$response = $this->do_post_request_review();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'This review request must be completed in Merchant Center.', $response->get_data()['message'] );
	}

	public function test_request_review_already_under_review() {
		$this->merchant->expects( $this->once() )
			->method( 'get_account_review_status' )
			->willReturn(
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->in_app_action() ]
				)
			);

		$this->merchant->expects( $this->once() )
			->method( 'trigger_review_action' )
			->willThrowException( new Exception( 'The account is already under review', 400 ) );

		$response = $this->do_post_request_review();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( RequestReviewStatuses::UNDER_REVIEW, $response->get_data()['status'] );
	}

	public function test_exception_in_status_route() {
		$this->merchant->expects( $this->exactly( 2 ) )
			->method( 'get_account_review_status' )
			->willThrowException( new Exception( 'error', 401 ) );

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
				$this->render_response(
					RequestReviewStatuses::SEVERITY_ERROR,
					'Account suspended',
					[ $this->in_app_action() ]
				)
			);

		$this->merchant->expects( $this->once() )
			->method( 'trigger_review_action' )
			->willThrowException( new Exception( 'error', 401 ) );

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
}
