<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\RequestReview;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test suit for AccountReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 * @property RequestReview|MockObject $request_review
 */
class AccountReviewControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_GET_REQUEST = '/wc/gla/mc/review';

	public function setUp() {
		parent::setUp();

		$this->request_review = $this->createMock( RequestReview::class );
		$this->controller     = new AccountReviewController( $this->server, $this->request_review );
		$this->controller->register();
	}

	public function test_route() {
		$this->request_review->method( 'get_next_attempt' )
		                     ->willReturn( mktime( 0, 0, 0 ) );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( mktime( 0, 0, 0 ), $response->get_data()['nextAttempt'] );
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_GET_REQUEST, $this->server->get_routes() );
	}

}
