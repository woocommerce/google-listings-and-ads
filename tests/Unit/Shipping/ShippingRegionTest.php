<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRegion;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class ShippingRegionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 */
class ShippingRegionTest extends UnitTest {
	public function test_generated_random_id_length_has_equal_to_or_more_than_six_digits() {
		$this->assertGreaterThanOrEqual( 6, strlen( ShippingRegion::generate_random_id() ) );
	}
}
