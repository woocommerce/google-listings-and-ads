<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\AccountReviewController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PhoneVerificationController;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PhoneVerificationException;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\RequestReview;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PhoneNumber;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Ctest suit for AccountReviewController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 * @group RequestReview
 * @property RESTServer $rest_server
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

	public function test_request_phone_verification() {
		$this->request_review->expects( $this->once() )
		                     ->method( 'get_next_attempt' )
		                     ->willReturn( time() );

		$response = $this->do_request( self::ROUTE_GET_REQUEST );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( time(), $response->get_data()['nextAttempt'] );
	}

}
