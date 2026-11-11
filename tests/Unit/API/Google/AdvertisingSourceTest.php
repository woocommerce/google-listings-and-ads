<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdvertisingSource;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdvertisingSourceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class AdvertisingSourceTest extends UnitTest {

	public function test_values_lists_product_and_page() {
		$this->assertSame( [ 'product', 'page' ], AdvertisingSource::values() );
	}

	public function test_get_default_is_product_when_merchant_center_is_connected() {
		$this->assertSame( AdvertisingSource::PRODUCT, AdvertisingSource::get_default( 1234 ) );
	}

	public function test_get_default_is_page_when_merchant_center_is_not_connected() {
		$this->assertSame( AdvertisingSource::PAGE, AdvertisingSource::get_default( 0 ) );
	}

	public function test_resolve_keeps_the_requested_source() {
		$this->assertSame( AdvertisingSource::PAGE, AdvertisingSource::resolve( AdvertisingSource::PAGE, 1234 ) );
		$this->assertSame( AdvertisingSource::PRODUCT, AdvertisingSource::resolve( AdvertisingSource::PRODUCT, 0 ) );
	}

	public function test_resolve_falls_back_to_the_default_when_nothing_is_requested() {
		$this->assertSame( AdvertisingSource::PRODUCT, AdvertisingSource::resolve( null, 1234 ) );
		$this->assertSame( AdvertisingSource::PAGE, AdvertisingSource::resolve( null, 0 ) );
	}
}
