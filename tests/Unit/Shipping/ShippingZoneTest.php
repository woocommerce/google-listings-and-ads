<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\Location;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRatesProcessor;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRateFlat;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRateFree;
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
									 new Location( 'US' ),
									 new Location( 'DE' ),
								 ]
							 );
		$this->methods_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn( [ new ShippingRateFree() ] );

		$this->assertEqualSets(
			[
				'US',
				'DE',
			],
			$this->shipping_zone->get_shipping_countries()
		);
	}

	public function test_returns_shipping_rates_for_country() {
		$this->locations_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn(
								 [
									 new Location( 'US', 'CA', [ '12345', '67890' ] ),
									 new Location( 'CA', 'BC', [ '12345', '67890' ] ),
								 ]
							 );
		$this->methods_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn( [ new ShippingRateFree() ] );

		$location_rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );
		$this->assertCount( 1, $location_rates );
		$this->assertInstanceOf( ShippingRateFree::class, $location_rates[0]->get_shipping_rate() );
		$this->assertEquals( 'US', $location_rates[0]->get_location()->get_country() );
		$this->assertEquals( 'CA', $location_rates[0]->get_location()->get_state() );
		$this->assertEqualSets( [ '12345', '67890' ], $location_rates[0]->get_location()->get_postcodes() );

		$location_rates = $this->shipping_zone->get_shipping_rates_for_country( 'CA' );
		$this->assertCount( 1, $location_rates );
		$this->assertInstanceOf( ShippingRateFree::class, $location_rates[0]->get_shipping_rate() );
		$this->assertEquals( 'CA', $location_rates[0]->get_location()->get_country() );
		$this->assertEquals( 'BC', $location_rates[0]->get_location()->get_state() );
		$this->assertEqualSets( [ '12345', '67890' ], $location_rates[0]->get_location()->get_postcodes() );
	}

	public function test_returns_shipping_rates_grouped_by_country() {
		$this->locations_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn(
								 [
									 new Location( 'US', 'CA' ),
									 new Location( 'CA', 'BC' ),
								 ]
							 );
		$this->methods_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn(
								 [
									 new ShippingRateFree(),
									 new ShippingRateFlat( 10 ),
								 ]
							 );

		$location_rates = $this->shipping_zone->get_shipping_rates_grouped_by_country( 'US' );
		$this->assertCount( 2, $location_rates );
		foreach ( $location_rates as $location_rate ) {
			if ( ! $location_rate->get_shipping_rate() instanceof ShippingRateFree && ! $location_rate->get_shipping_rate() instanceof ShippingRateFlat ) {
				$this->fail( 'Expected only free shipping and flat rate shipping rates' );
			}
		}
	}

	public function test_ignores_zones_with_no_methods() {
		$this->locations_parser->expects( $this->once() )
							 ->method( 'parse' )
							 ->willReturn( [ new Location( 'US' ) ] );
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
							 ->willReturn( [ new ShippingRateFree() ] );

		$this->assertEmpty( $this->shipping_zone->get_shipping_countries() );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
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
							  ->willReturnCallback( function ( $location_rates ) {
								  return $location_rates;
							  } );

		$this->shipping_zone = new ShippingZone( $this->wc, $this->locations_parser, $this->methods_parser, $this->rates_processor );
	}
}
