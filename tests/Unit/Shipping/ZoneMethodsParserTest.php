<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ZoneMethodsParser;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Shipping_Flat_Rate;
use WC_Shipping_Free_Shipping;
use WC_Shipping_Zone;

/**
 * Class ZoneMethodsParserTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC     $wc
 * @property ZoneMethodsParser $methods_parser
 */
class ZoneMethodsParserTest extends UnitTest {
	public function test_returns_flat_rate_methods() {
		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ZoneMethodsParser::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'cost', null, 10 ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $flat_rate ] );

		$shipping_rates = $this->methods_parser->parse( $zone );
		$this->assertCount( 1, $shipping_rates );
		$this->assertEquals( 10, $shipping_rates[0]->get_rate() );
	}

	public function test_returns_flat_rate_methods_including_shipping_classes() {
		// Return three sample shipping classes.
		$light_class          = new \stdClass();
		$light_class->term_id = 0;
		$light_class->slug    = 'light';
		$heavy_class          = new \stdClass();
		$heavy_class->term_id = 1;
		$heavy_class->slug    = 'heavy';
		$qty_class            = new \stdClass();
		$qty_class->term_id   = 2;
		$qty_class->slug      = 'qty';
		$shipping_classes     = [ $light_class, $heavy_class, $qty_class ];
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_classes' )
			->willReturn( $shipping_classes );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ZoneMethodsParser::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'cost', null, 10 ],
					[ 'class_cost_0', null, 5 ],
					[ 'class_cost_1', null, 15 ],
					[ 'class_cost_2', null, '[qty] / 10' ],
					[ 'no_class_cost', null, 2 ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $flat_rate ] );

		$shipping_rates = $this->methods_parser->parse( $zone );

		// The shipping class with a dynamic price should be ignored. The third rate is the one with no class.
		$this->assertCount( 3, $shipping_rates );

		$classes = array_map(
			function ( ShippingRate $shipping_rate ) {
				return $shipping_rate->get_applicable_classes();
			},
			$shipping_rates
		);
		$this->assertEqualSets(
			[
				[],
				[ 'light' ],
				[ 'heavy' ],
			],
			$classes
		);

		foreach ( $shipping_rates as $shipping_rate ) {
			if ( [] === $shipping_rate->get_applicable_classes() ) {
				// The `no_class_cost` should be added to the flat rate method cost (10+2=12).
				$this->assertEquals( 12, $shipping_rate->get_rate() );
			} elseif ( [ 'light' ] === $shipping_rate->get_applicable_classes() ) {
				// The shipping class costs should be added to the flat rate method cost (10+5=15).
				$this->assertEquals( 15, $shipping_rate->get_rate() );
			} elseif ( [ 'heavy' ] === $shipping_rate->get_applicable_classes() ) {
				// The shipping class costs should be added to the flat rate method cost (10+15=25).
				$this->assertEquals( 25, $shipping_rate->get_rate() );
			}
		}
	}

	/**
	 * @param string $requires
	 *
	 * @dataProvider return_free_shipping_min_amount_requirements
	 */
	public function test_returns_free_shipping_methods( string $requires ) {
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ZoneMethodsParser::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'requires', null, $requires ],
					[ 'min_amount', null, 99.99 ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $free_shipping ] );

		$shipping_rates = $this->methods_parser->parse( $zone );
		$this->assertCount( 1, $shipping_rates );
		$this->assertEquals( 0, $shipping_rates[0]->get_rate() );
		$this->assertEquals( 99.99, $shipping_rates[0]->get_min_order_amount() );
	}

	/**
	 * @param string $requires
	 *
	 * @dataProvider return_free_shipping_coupon_requirements
	 */
	public function test_ignores_free_shipping_methods_if_they_require_coupons( string $requires ) {
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ZoneMethodsParser::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'requires', null, $requires ],
					[ 'min_amount', null, 99.99 ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $free_shipping ] );

		$this->assertEmpty( $this->methods_parser->parse( $zone ) );
	}

	public function test_ignores_unsupported_methods() {
		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn(
				[
					(object) [
						'enabled' => true,
						'title'   => 'Unsupported method',
						'id'      => 'a_random_unsupported_method',
					],
				]
			);

		$this->assertEmpty( $this->methods_parser->parse( $zone ) );
	}

	public function test_ignores_flat_rate_methods_with_no_rate() {
		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ZoneMethodsParser::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'cost', null ],
					[ 'no_class_cost', null ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $flat_rate ] );

		$this->assertEmpty( $this->methods_parser->parse( $zone ) );
	}

	public function test_ignores_flat_rate_methods_with_non_numeric_rate() {
		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ZoneMethodsParser::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnMap(
				[
					[ 'cost', '[qty] * 5' ],
				]
			);

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( [ $flat_rate ] );

		$this->assertEmpty( $this->methods_parser->parse( $zone ) );
	}

	/**
	 * Returns two options for the `requires` options of a free-shipping method that require a minimum order amount.
	 *
	 * @return array
	 */
	public function return_free_shipping_min_amount_requirements(): array {
		return [
			[ 'min_amount' ],
			[ 'either' ],
		];
	}

	/**
	 * Returns two options for the `requires` options of a free-shipping method that require a coupon.
	 *
	 * @return array
	 */
	public function return_free_shipping_coupon_requirements(): array {
		return [
			[ 'coupon' ],
			[ 'both' ],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		$this->wc             = $this->createMock( WC::class );
		$this->methods_parser = new ZoneMethodsParser( $this->wc );
	}
}
