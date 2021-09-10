<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

/**
 * Trait Coupon
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait CouponTrait {
	use SettingsTrait;

	/**
	 * Generates and returns a mock of a simple WC_Coupon object
	 *
	 * @return MockObject|WC_Coupon
	 */
	public function generate_simple_coupon_mock() {
		$coupon = $this->createMock( WC_Coupon::class );

		$coupon->expects( $this->any() )
				->method( 'get_id' )
				->willReturn( rand() );

		return $coupon;
	}
}
