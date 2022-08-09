<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\PolicyComplianceCheck;
use WC_Payment_Gateway;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheckTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\MerchantCenter
 *
 * @property  MockObject|WC		$wc
 * @property  MockObject|GoogleHelper	$google_helper
 * @property  MockObject|TargetAudience	$target_audience
 * @property  PolicyComplianceCheck	$policy_compliance_check
 */
class PolicyComplianceCheckTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc		= $this->createMock( WC::class );
		$this->google_helper	= $this->createMock( GoogleHelper::class );
		$this->target_audience	= $this->createMock( TargetAudience::class );

		$this->policy_compliance_check = new PolicyComplianceCheck( $this->wc, $this->google_helper, $this->target_audience );
	}

	public function test_website_accessible() {
		$this->wc->expects( $this->any() )
					   ->method( 'get_allowed_countries' )
					   ->willReturn( ['AU' => 'Australia', 'AT' => 'Austria', 'CA' => 'Canada', 'US' => 'United States'] );
		$this->target_audience->expects( $this->any() )
					   ->method( 'get_target_countries' )
					   ->willReturn( ['AU' , 'US' ] );

		$this->assertTrue($this->policy_compliance_check->is_accessible());
	}

	public function test_website_not_accessible() {
		$this->wc->expects( $this->any() )
					   ->method( 'get_allowed_countries' )
					   ->willReturn( ['AU' => 'Australia', 'AT' => 'Austria', 'CA' => 'Canada', 'US' => 'United States'] );
		$this->target_audience->expects( $this->any() )
					   ->method( 'get_target_countries' )
					   ->willReturn( ['FR', 'US' ] );

		$this->assertFalse($this->policy_compliance_check->is_accessible());
	}

	public function test_payment_gateways() {
					   $payment_gateways = [
						'stripe'	=> $this->createMock( WC_Payment_Gateway::class ),
						'paypal'	=> $this->createMock( WC_Payment_Gateway::class ),
					];

		$this->assertTrue($this->policy_compliance_check->has_payment_gateways());
	}

	public function test_empty_payment_gateways() {
		$this->wc->expects( $this->any() )
					   ->method( 'get_available_payment_gateways' )
					   ->willReturn( [] );

		$this->assertFalse($this->policy_compliance_check->has_payment_gateways());
	}
}
