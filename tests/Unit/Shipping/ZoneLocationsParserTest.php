<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\Location;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ZoneLocationsParser;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Countries;
use WC_Shipping_Zone;

/**
 * Class ZoneLocationsParserTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC           $wc
 * @property MockObject|GoogleHelper $google_helper
 * @property ZoneLocationsParser     $locations_parser
 */
class ZoneLocationsParserTest extends UnitTest {
	public function test_returns_state_locations_if_regional_shipping_supported() {
		$zone_locations = [
			(object) [
				'code' => 'US:NV',
				'type' => 'state',
			],
		];

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			 ->method( 'get_zone_locations' )
			 ->willReturn( $zone_locations );

		$this->google_helper->expects( $this->any() )
							->method( 'is_country_supported' )
							->with( 'US' )
							->willReturn( true );

		$this->google_helper->expects( $this->any() )
							->method( 'does_country_support_regional_shipping' )
							->with( 'US' )
							->willReturn( true );

		$parsed_locations = $this->locations_parser->parse( $zone );

		$this->assertCount( 1, $parsed_locations );
		$this->assertEquals( 'US', $parsed_locations[0]->get_country() );
		$this->assertEquals( 'NV', $parsed_locations[0]->get_state() );
	}

	public function test_returns_country_locations_if_regional_shipping_unsupported() {
		$zone_locations = [
			(object) [
				'code' => 'XX:NV',
				'type' => 'state',
			],
		];

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			 ->method( 'get_zone_locations' )
			 ->willReturn( $zone_locations );

		$this->google_helper->expects( $this->any() )
							->method( 'is_country_supported' )
							->with( 'XX' )
							->willReturn( true );
		$this->google_helper->expects( $this->any() )
							->method( 'does_country_support_regional_shipping' )
							->with( 'XX' )
							->willReturn( false );

		$parsed_locations = $this->locations_parser->parse( $zone );

		$this->assertCount( 1, $parsed_locations );
		$this->assertEquals( 'XX', $parsed_locations[0]->get_country() );
		$this->assertEmpty( $parsed_locations[0]->get_state() );
	}

	public function test_returns_postcodes_for_all_other_locations() {
		$zone_locations = [
			(object) [
				'code' => 'US:CA',
				'type' => 'state',
			],
			(object) [
				'code' => 'FR',
				'type' => 'country',
			],
			(object) [
				'code' => 'EU',
				'type' => 'continent',
			],
			(object) [
				'code' => '12345',
				'type' => 'postcode',
			],
			(object) [
				'code' => '67890',
				'type' => 'postcode',
			],
		];

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			 ->method( 'get_zone_locations' )
			 ->willReturn( $zone_locations );


		// Mock the WC_Countries class to return a predefined list of countries for the EU continent.
		$wc_countries = $this->createMock( WC_Countries::class );
		$wc_countries->expects( $this->any() )
					 ->method( 'get_continents' )
					 ->willReturn( [
						 'EU' => [
							 'name'      => 'Europe',
							 'countries' => [
								 'DE',
								 'DK',
							 ],
						 ],
					 ] );
		$this->wc->expects( $this->any() )
				 ->method( 'get_wc_countries' )
				 ->willReturn( $wc_countries );

		$this->google_helper->expects( $this->any() )
							->method( 'get_mc_supported_countries' )
							->willReturn(
								[
									'DE',
									'DK',
								]
							);
		$this->google_helper->expects( $this->any() )
							->method( 'is_country_supported' )
							->willReturn( true );
		$this->google_helper->expects( $this->any() )
							->method( 'does_country_support_regional_shipping' )
							->willReturn( true );

		$parsed_locations = $this->locations_parser->parse( $zone );

		$this->assertCount( 4, $parsed_locations );
		foreach ( $parsed_locations as $location ) {
			$this->assertEqualSets( [ '12345', '67890' ], $location->get_postcodes() );
		}
	}

	public function test_returns_only_supported_locations() {
		$zone_locations = [
			// State from an unsupported country.
			(object) [
				'code' => 'XX:NV',
				'type' => 'state',
			],
			// State from a supported country.
			(object) [
				'code' => 'US:NV',
				'type' => 'state',
			],
			// Unsupported country.
			(object) [
				'code' => 'YY',
				'type' => 'country',
			],
			// Supported country.
			(object) [
				'code' => 'US',
				'type' => 'country',
			],
			// Continent with both supported and unsupported countries.
			(object) [
				'code' => 'EU',
				'type' => 'continent',
			],
			// Non-existent continent.
			(object) [
				'code' => 'ZZ',
				'type' => 'continent',
			],
		];

		$zone = $this->createMock( WC_Shipping_Zone::class );
		$zone->expects( $this->any() )
			 ->method( 'get_zone_locations' )
			 ->willReturn( $zone_locations );

		// Mock the WC_Countries class to return a predefined list of countries for the EU continent.
		$wc_countries = $this->createMock( WC_Countries::class );
		$wc_countries->expects( $this->any() )
					 ->method( 'get_continents' )
					 ->willReturn( [
						 'EU' => [
							 'name'      => 'Europe',
							 'countries' => [
								 // Supported country.
								 'DE',
								 // Unsupported country.
								 'AA',
							 ],
						 ],
					 ] );
		$this->wc->expects( $this->any() )
				 ->method( 'get_wc_countries' )
				 ->willReturn( $wc_countries );

		$this->google_helper->expects( $this->any() )
							->method( 'get_mc_supported_countries' )
							->willReturn(
								[
									'US',
									'DE'
								]
							);
		$this->google_helper->expects( $this->any() )
							->method( 'is_country_supported' )
							->willReturnMap(
								[
									[ 'US', true ],
									[ 'DE', true ],
									[ 'XX', false ],
									[ 'YY', false ],
									[ 'AA', false ],
								]
							);
		$this->google_helper->expects( $this->any() )
							->method( 'does_country_support_regional_shipping' )
							->with( 'US' )
							->willReturn( true );

		$parsed_locations = $this->locations_parser->parse( $zone );

		$this->assertCount( 3, $parsed_locations );

		$location_countries = array_map(
			function ( Location $location ) {
				return $location->get_country();
			},
			$parsed_locations
		);
		$this->assertEqualSets(
			[
				'US',
				// Repeated because of the "US:NV" state location entry.
				'US',
				'DE',
			],
			$location_countries
		);

		$states = array_map(
			function ( Location $location ) {
				return $location->get_state();
			},
			$parsed_locations
		);
		$this->assertEquals(
			[
				'NV',
				// Two null entries for the country locations without a state.
				null,
				null,
			],
			$states
		);
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->wc               = $this->createMock( WC::class );
		$this->google_helper    = $this->createMock( GoogleHelper::class );
		$this->locations_parser = new ZoneLocationsParser( $this->wc, $this->google_helper );
	}
}
