<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRatesProcessor;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRegion;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ZoneLocationsParser;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ZoneMethodsParser;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Shipping_Zone;

/**
 * Class ShippingZoneTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC                     $wc
 * @property MockObject|ZoneLocationsParser    $locations_parser
 * @property MockObject|ZoneMethodsParser      $methods_parser
 * @property MockObject|LocationRatesProcessor $rates_processor
 * @property ShippingZone                      $shipping_zone
 */
class ShippingZoneTest extends UnitTest {
	public function test_returns_shipping_countries() {
		$this->locations_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn(
				[
					new ShippingLocation( 2840, 'US' ),
					new ShippingLocation( 2276, 'DE' ),
				]
			);
		$this->methods_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ new ShippingRate( 0 ) ] );

		$this->assertEqualSets(
			[
				'US',
				'DE',
			],
			$this->shipping_zone->get_shipping_countries()
		);
	}

	public function test_returns_shipping_rates_for_country() {
		$postcodes_1 = [
			new PostcodeRange( '12345' ),
			new PostcodeRange( '67890' ),
		];
		$region_1    = new ShippingRegion( '123456', 'US', $postcodes_1 );

		$postcodes_2 = [
			new PostcodeRange( '23456' ),
			new PostcodeRange( '78901' ),
		];
		$region_2    = new ShippingRegion( '234567', 'AU', $postcodes_2 );
		$this->locations_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn(
				[
					new ShippingLocation( 21137, 'US', 'CA', $region_1 ),
					new ShippingLocation( 20035, 'AU', 'NSW', $region_2 ),
				]
			);
		$this->methods_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ new ShippingRate( 0 ) ] );

		$location_rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );
		$this->assertCount( 1, $location_rates );
		$this->assertEquals( 0, $location_rates[0]->get_shipping_rate()->get_rate() );
		$this->assertEquals( 'US', $location_rates[0]->get_location()->get_country() );
		$this->assertEquals( 'CA', $location_rates[0]->get_location()->get_state() );
		$this->assertEquals( $region_1, $location_rates[0]->get_location()->get_shipping_region() );

		$location_rates = $this->shipping_zone->get_shipping_rates_for_country( 'AU' );
		$this->assertCount( 1, $location_rates );
		$this->assertEquals( 0, $location_rates[0]->get_shipping_rate()->get_rate() );
		$this->assertEquals( 'AU', $location_rates[0]->get_location()->get_country() );
		$this->assertEquals( 'NSW', $location_rates[0]->get_location()->get_state() );
		$this->assertEquals( $region_2, $location_rates[0]->get_location()->get_shipping_region() );

		// Test non-existent country.
		$location_rates = $this->shipping_zone->get_shipping_rates_for_country( 'XX' );
		$this->assertEmpty( $location_rates );
	}

	public function test_returns_shipping_rates_grouped_by_country() {
		$this->locations_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn(
				[
					new ShippingLocation( 21137, 'US', 'CA' ),
					new ShippingLocation( 20035, 'AU', 'NSW' ),
				]
			);
		$this->methods_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn(
				[
					new ShippingRate( 0 ),
					new ShippingRate( 10 ),
				]
			);

		$location_rates = $this->shipping_zone->get_shipping_rates_grouped_by_country( 'US' );
		$this->assertCount( 2, $location_rates );
		foreach ( $location_rates as $location_rate ) {
			if ( ! in_array( $location_rate->get_shipping_rate()->get_rate(), [ 0.0, 10.0 ], true ) ) {
				$this->fail( 'Expected only free shipping and flat rate shipping rates' );
			}
		}
	}

	public function test_ignores_zones_with_no_methods() {
		$this->locations_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ new ShippingLocation( 2840, 'US' ) ] );
		$this->methods_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [] );

		$this->assertEmpty( $this->shipping_zone->get_shipping_countries() );
	}

	public function test_ignores_zones_with_no_locations() {
		$this->locations_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [] );
		$this->methods_parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ new ShippingRate( 0 ) ] );

		$this->assertEmpty( $this->shipping_zone->get_shipping_countries() );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc = $this->createMock( WC::class );

		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $this->createMock( WC_Shipping_Zone::class ) );

		$this->locations_parser = $this->createMock( ZoneLocationsParser::class );
		$this->methods_parser   = $this->createMock( ZoneMethodsParser::class );

		$this->rates_processor = $this->createMock( LocationRatesProcessor::class );
		$this->rates_processor->expects( $this->any() )
			->method( 'process' )
			->willReturnCallback(
				function ( $location_rates ) {
					return $location_rates;
				}
			);

		$this->shipping_zone = new ShippingZone( $this->wc, $this->locations_parser, $this->methods_parser, $this->rates_processor );
	}
}
