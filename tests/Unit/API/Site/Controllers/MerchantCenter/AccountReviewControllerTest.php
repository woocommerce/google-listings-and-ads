<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;

/**
 * Test suit for AccountReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 */
class AccountReviewControllerTest extends RESTControllerUnitTest {

	protected const ROUTE_GET_REQUEST = '/wc/gla/mc/review';

	public function setUp() {
		parent::setUp();

		$this->controller     = new AccountReviewController( $this->server );
		$this->controller->register();
	}

	public function test_route() {
		$response = $this->do_request( self::ROUTE_GET_REQUEST );

		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertTrue( isset( $response_data['status'] ) );
		$this->assertTrue( isset( $response_data['issues'] ) );
	}


	public function test_register_route() {
		$this->assertArrayHasKey( self::ROUTE_GET_REQUEST, $this->server->get_routes() );
	}

}
