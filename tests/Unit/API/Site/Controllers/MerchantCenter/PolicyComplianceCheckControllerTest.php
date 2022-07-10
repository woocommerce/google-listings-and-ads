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

		$this->policy_compliance_check = $this->createMock( PolicyComplianceCheck::class );
		$this->controller         = new PolicyComplianceCheckController( $this->server, $this->policy_compliance_check );
		$this->controller->register();
		$this->controller->register_routes();
	}

	public function test_policy_check() {
		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'get_allowed_countries' )
		                         ->willReturn([ 'allowed_countries' => ['US', 'UK'] ]);

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'get_is_store_ssl' )
		                         ->willReturn( [ 'store_ssl' => true ] );

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_payment_gateways' )
		                         ->willReturn( [ 'payment_gateways' => true ] );

		$this->policy_compliance_check->expects( $this->once() )
		                         ->method( 'has_refund_return_policy_page' )
		                         ->willReturn( [ 'refund_return_policy_page' => true ] );

		$response = $this->do_request( self::POLICY_CHECK, 'GET', [] );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array_merge(
				[ 'allowed_countries' => ['US', 'UK'] ],
				[ 'store_ssl' => true ],
				[ 'payment_gateways' => true ],
				[ 'refund_return_policy_page' => true ]),
		$response->get_data());
	}

}
