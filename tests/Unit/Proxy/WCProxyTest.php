<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AccountService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads\AccountController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Coupons_Controller;

/**
 * Class AccountControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\Ads
 */
class WCProxyTest extends RESTControllerUnitTest {

	/** @var MockObject|AccountService $account */
	protected $account;

	/** @var AccountController $controller */
	protected $controller;

	public function setUp(): void {
		parent::setUp();
		do_action( 'rest_api_init' );
	}


	public function test_get_billing_status() {

		$meta = [
			'_private_meta' => 'private',
			'public_meta'   => 'public',
			'_wc_gla_visibility' => 'sync-and-show',
		];

		$coupon_1 = CouponHelper::create_coupon( 'dummycoupon-1', 'publish', $meta );
		$coupon_2 = CouponHelper::create_coupon( 'dummycoupon-2', 'publish', $meta );

		delete_post_meta( $coupon_1->get_id(), 'customer_email' );
		delete_post_meta( $coupon_2->get_id(), 'customer_email' );

		$response = $this->do_request( '/wc/v3/coupons', 'GET', ['gla_syncable' => 1] );

		foreach ( $response->get_data() as $coupon ) {
			var_dump( $coupon['meta_data'] );
			//$this->assertArrayNotHasKey( '_private_meta', $coupon );
			//$this->assertArrayHasKey( 'public_meta', $coupon );
		}

		//$this->assertEquals( self::TEST_BILLING_STATUS_DATA, $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

}
