<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class PostcodeRangeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 */
class PostcodeRangeTest extends UnitTest {

	public function test_from_string() {
		$range = PostcodeRange::from_string( '11111...22222' );
		$this->assertEquals( '11111', $range->get_start_code() );
		$this->assertEquals( '22222', $range->get_end_code() );

		$simple_code = PostcodeRange::from_string( '11111' );
		$this->assertEquals( '11111', $simple_code->get_start_code() );
		$this->assertEmpty( $simple_code->get_end_code() );
	}

	public function test_to_string() {
		$range = new PostcodeRange( '11111', '22222' );
		$this->assertEquals( '11111...22222', (string) $range );

		$simple_code = new PostcodeRange( '11111' );
		$this->assertEquals( '11111', (string) $simple_code );
	}
}
