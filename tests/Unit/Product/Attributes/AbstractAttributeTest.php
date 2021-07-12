<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Multipack;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class AbstractAttributeTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class AbstractAttributeTest extends UnitTest {
	public function test_casts_value_based_on_value_type() {
		$this->assertIsString( ( new Brand( 1 ) )->get_value() );
		$this->assertEquals( '1', ( new Brand( 1 ) )->get_value() );

		$this->assertIsBool( ( new IsBundle( 'true' ) )->get_value() );
		$this->assertTrue( ( new IsBundle( 'true' ) )->get_value() );
		$this->assertIsBool( ( new IsBundle( 'yes' ) )->get_value() );
		$this->assertTrue( ( new IsBundle( 'yes' ) )->get_value() );
		$this->assertIsBool( ( new IsBundle( true ) )->get_value() );
		$this->assertTrue( ( new IsBundle( true ) )->get_value() );

		$this->assertIsInt( ( new Multipack( '1234' ) )->get_value() );
		$this->assertEquals( 1234, ( new Multipack( '1234' ) )->get_value() );
	}

	public function test_trims_string_value() {
		$this->assertEquals( 'Google', ( new Brand( ' Google     ' ) )->get_value() );
	}

	public function test_casts_to_null_if_empty_string_value() {
		$this->assertNull( ( new Brand( '' ) )->get_value() );
	}
}
