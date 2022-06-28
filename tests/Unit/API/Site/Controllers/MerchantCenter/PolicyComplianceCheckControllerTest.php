<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\PolicyComplianceCheckController;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\RESTControllerUnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PolicyComplianceCheckControllerTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Site\Controllers\MerchantCenter
 *
 * @property RESTServer                   $rest_server
 * @property PolicyComplianceCheck|MockObject $policy_check
 */
class PolicyComplianceCheckControllerTest extends RESTControllerUnitTest {

	protected const ALLOWED_COUNTRIES     = '/wc/gla/mc/policy_check/allowed_countries';
	protected const STORE_SSL     = '/wc/gla/mc/policy_check/store_ssl';
	protected const PAYMENT_GATEWAYS     = '/wc/gla/mc/policy_check/payment_gateways';
	protected const REFUND_RETURN     = '/wc/gla/mc/policy_check/refund_return_policy';

	public function setUp(): void {
		parent::setUp();

		$this->policy_compliance_check = $this->createMock( PolicyComplianceCheck::class );
		$this->controller         = new PolicyComplianceCheckController( $this->server, $this->policy_compliance_check );
		$this->controller->register();
		$this->controller->register_routes();
	}

	public function test_allowed_countries() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'get_allowed_countries' )
		                         ->willReturn([ 'allowed_countries' => ['US', 'UK'] ]);

		$response = $this->do_request( self::ALLOWED_COUNTRIES, 'POST', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'allowed_countries' => ['US', 'UK'] ], $response->get_data() );
	}

	public function test_store_ssl() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'get_is_store_ssl' )
		                         ->willReturn( [ 'ssl' => true ] );

		$response = $this->do_request( self::STORE_SSL, 'POST', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'store_ssl' => true ], $response->get_data() );
	}

	public function test_payment_gateways() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_payment_gateways' )
		                         ->willReturn( [ 'payment_gateways' => true ] );

		$response = $this->do_request( self::PAYMENT_GATEWAYS, 'POST', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'payment_gateways' => true ], $response->get_data() );
	}

	public function test_refund_return_policy() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_refund_return_policy_page' )
		                         ->willReturn( [ 'refund_return_policy_page' => true ] );

		$response = $this->do_request( self::REFUND_RETURN, 'POST', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( [ 'refund_return_policy_page' => true ], $response->get_data() );
	}

}
