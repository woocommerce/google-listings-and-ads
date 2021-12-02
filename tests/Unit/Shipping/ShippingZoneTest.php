<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Countries;
use WC_Shipping_Zone;

/**
 * Class BatchProductHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC $wc
 * @property ShippingZone  $shipping_zone
 */
class ShippingZoneTest extends UnitTest {

	public function test_returns_shipping_countries() {
		$shipping_zones = [
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object)[
						'code' => 'US:NV',
						'type' => 'state',
					],
					(object) [
						'code' => 'US:CA',
						'type' => 'state',
					],
				],
			],
			[
				'id'             => 2,
				'zone_id'        => 2,
				'zone_name'      => 'EU branches',
				'zone_locations' =>  [
					(object) [
						'code' => 'GB',
						'type' => 'country',
					],
					(object) [
						'code' => 'FR',
						'type' => 'country',
					],
				],
			],
			[
				'id'             => 3,
				'zone_id'        => 3,
				'zone_name'      => 'EU (Other)',
				'zone_locations' => [
					(object) [
						'code' => 'EU',
						'type' => 'continent',
					],
				],
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
				 ->method( 'get_shipping_zones' )
				 ->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
				 ->method( 'get_shipping_zone' )
				 ->willReturnCallback( function ( $zone_id ) use ( $shipping_zones ) {
					 $zone = $this->createMock( WC_Shipping_Zone::class );
					 $zone->expects( $this->any() )
						  ->method( 'get_zone_locations' )
						  ->willReturn( $shipping_zones[ $zone_id - 1 ]['zone_locations'] );

					 return $zone;
				 } );

		// Mock the WC_Countries class to return the list of countries for the EU continent.
		$wc_countries = $this->createMock( WC_Countries::class );
		$wc_countries->expects( $this->any() )
					 ->method( 'get_continents' )
					 ->willReturn( [
						 'EU' => [
							 'name'      => 'Europe',
							 'countries' => [
								 'OO1',
								 // A random country code, not supported by Merchant Center. This should be ignored.
								 'OO2',
								 // A random country code, not supported by Merchant Center. This should be ignored.
								 'GB',
								 'FR',
								 'DE',
								 'DK',
								 // And many more ...
							 ],
						 ],
					 ] );
		$this->wc->expects( $this->any() )
				 ->method( 'get_wc_countries' )
				 ->willReturn( $wc_countries );

		$this->assertEqualSets(
			[
				'US',
				'GB',
				'FR',
				'DE',
				'DK',
			],
			$this->shipping_zone->get_shipping_countries()
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->wc            = $this->createMock( WC::class );
		$this->shipping_zone = new ShippingZone( $this->wc );
	}
}
