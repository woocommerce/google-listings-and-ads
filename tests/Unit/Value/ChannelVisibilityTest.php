<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;

/**
 * Class ChannelVisibilityTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value
 */
class ChannelVisibilityTest extends UnitTest {
	public function test_constructor_and_get() {
		$this->assertEquals(
			'sync-and-show',
			( new ChannelVisibility( 'sync-and-show' ) )->get()
		);

		$this->assertEquals(
			'dont-sync-and-show',
			( new ChannelVisibility( 'dont-sync-and-show' ) )->get()
		);
	}

	public function test_constructor_invalid_value() {
		$this->expectException( InvalidValue::class );

		new ChannelVisibility( '123' );
	}

	public function test_cast() {
		$this->assertEquals(
			'sync-and-show',
			ChannelVisibility::cast( 'sync-and-show' )->get()
		);

		$this->assertEquals(
			'dont-sync-and-show',
			ChannelVisibility::cast( 'dont-sync-and-show' )->get()
		);
	}

	public function test_cast_invalid_value() {
		$this->expectException( InvalidValue::class );

		ChannelVisibility::cast( '123' );
	}

	public function test_get_value_options() {
		$this->assertEquals(
			[
				'sync-and-show'      => 'Sync and show',
				'dont-sync-and-show' => "Don't Sync and show",
			],
			ChannelVisibility::get_value_options(),
		);
	}

	public function test_to_string() {
		$this->assertEquals(
			'sync-and-show',
			(string) ( new ChannelVisibility( 'sync-and-show' ) )
		);
	}
}
