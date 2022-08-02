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

	protected const POLICY_CHECK     	= '/wc/gla/mc/policy_check';

	public function setUp(): void {
		parent::setUp();

		$this->policy_compliance_check	= $this->createMock( PolicyComplianceCheck::class );
		$this->controller         	= new PolicyComplianceCheckController( $this->server, $this->policy_compliance_check );
		$this->controller->register();
		$this->controller->register_routes();
	}

	public function test_policy_check() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'is_accessible' )
		                         ->willReturn(true);

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_restriction' )
		                         ->willReturn(false);

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_page_not_found_error' )
		                         ->willReturn(false);

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_redirects' )
		                         ->willReturn(false);

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'get_is_store_ssl' )
		                         ->willReturn( true );

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_payment_gateways' )
		                         ->willReturn( true );

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_refund_return_policy_page' )
		                         ->willReturn( true );

		$response = $this->do_request( self::POLICY_CHECK, 'GET', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
		[
			'allowed_countries'    	=> true,
			'robots_restriction'    => false,
			'page_not_found_error'            => false,
			'page_redirects'        => false,
			'store_ssl'         	=> true,
			'payment_gateways'  	=> true,
			'refund_returns' 	=> true,
		],
		$response->get_data());
	}
}
