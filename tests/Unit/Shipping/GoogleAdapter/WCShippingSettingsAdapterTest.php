<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRegion;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class WCShippingSettingsAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter
 */
class WCShippingSettingsAdapterTest extends UnitTest {
	public function test_creates_rate_group_for_country_postal_rates() {
		$region_1        = new ShippingRegion( '123456', 'US', [ new PostcodeRange( '1000' ) ] );
		$location_1      = new ShippingLocation( 1, 'US', null, $region_1 );
		$location_rate_1 = new LocationRate( $location_1, new ShippingRate( 100 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
				],
				'delivery_times'    => [
					'US' => [
						'time'     => 2,
						'max_time' => 3,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]['rateGroups'] );
		$this->assertArrayHasKey( 'postalCodeGroupNames', $services[0]['rateGroups'][0]['mainTable']['rowHeaders'] );
		$this->assertArrayHasKey( '123456', $settings->get_regions() );
	}

	public function test_creates_rate_group_for_state_rates() {
		$location_1      = new ShippingLocation( 1, 'US', 'CA' );
		$location_rate_1 = new LocationRate( $location_1, new ShippingRate( 100 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
				],
				'delivery_times'    => [
					'US' => [
						'time'     => 2,
						'max_time' => 3,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]['rateGroups'] );
		$this->assertArrayHasKey( 'locations', $services[0]['rateGroups'][0]['mainTable']['rowHeaders'] );
		$this->assertEmpty( $settings->get_regions() );
	}

	public function test_creates_separate_services_per_country_and_min_order_amount() {
		$min_order_rate = new ShippingRate( 0 );
		$min_order_rate->set_min_order_amount( 1000 );

		$location_rate_1 = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );
		$location_rate_2 = new LocationRate( new ShippingLocation( 1, 'US' ), $min_order_rate );
		$location_rate_3 = new LocationRate( new ShippingLocation( 2, 'AU' ), new ShippingRate( 200 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1, $location_rate_2 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_3 ] ),
				],
				'delivery_times'    => [
					'AU' => [
						'time'     => 1,
						'max_time' => 2,
					],
					'US' => [
						'time'     => 2,
						'max_time' => 3,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 3, $services );

		$min_order_services = array_filter(
			$services,
			function ( array $service ) {
				return isset( $service['minimumOrderValue'] );
			}
		);
		$this->assertCount( 1, $min_order_services );

		$min_order_service = $min_order_services[ array_key_first( $min_order_services ) ];
		$this->assertEquals( 'US', $min_order_service['deliveryCountries'][0] );
		$this->assertEquals( '1000000000', $min_order_service['minimumOrderValue']['amountMicros'] );
		$this->assertEquals( 'USD', $min_order_service['minimumOrderValue']['currencyCode'] );
	}

	public function test_sets_regions() {
		$region_1        = new ShippingRegion(
			'123456',
			'US',
			[
				new PostcodeRange( '1000' ),
				new PostcodeRange( '2000', '2001' ),
			]
		);
		$location_1      = new ShippingLocation( 1, 'US', null, $region_1 );
		$location_rate_1 = new LocationRate( $location_1, new ShippingRate( 100 ) );

		$region_2        = new ShippingRegion( '234567', 'US', [ new PostcodeRange( '9000', '9001' ) ] );
		$location_2      = new ShippingLocation( 2, 'US', 'CA', $region_2 );
		$location_rate_2 = new LocationRate( $location_2, new ShippingRate( 200 ) );

		$region_3        = new ShippingRegion( '345678', 'AU', [ new PostcodeRange( '9000', '9001' ) ] );
		$location_3      = new ShippingLocation( 3, 'AU', 'NSW', $region_3 );
		$location_rate_3 = new LocationRate( $location_3, new ShippingRate( 300 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1, $location_rate_2 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_3 ] ),
				],
				'delivery_times'    => [
					'AU' => [
						'time'     => 1,
						'max_time' => 2,
					],
					'US' => [
						'time'     => 2,
						'max_time' => 3,
					],
				],
			]
		);

		$regions = $settings->get_regions();

		$this->assertCount( 3, $regions );
		$this->assertEqualSets(
			[
				'123456',
				'234567',
				'345678',
			],
			array_keys( $regions )
		);

		foreach ( $regions as $region_id => $region ) {
			switch ( $region_id ) {
				case '123456':
					$this->assertEquals( 'US', $region['postalCodeArea']['regionCode'] );
					$this->assertCount( 2, $region['postalCodeArea']['postalCodes'] );
					foreach ( $region['postalCodeArea']['postalCodes'] as $postal_code ) {
						if ( '2000' === $postal_code['begin'] ) {
							$this->assertEquals( '2001', $postal_code['end'] );
						} else {
							$this->assertEquals( '1000', $postal_code['begin'] );
						}
					}
					break;
				case '234567':
					$this->assertEquals( 'US', $region['postalCodeArea']['regionCode'] );
					$this->assertCount( 1, $region['postalCodeArea']['postalCodes'] );
					$this->assertEquals( '9000', $region['postalCodeArea']['postalCodes'][0]['begin'] );
					$this->assertEquals( '9001', $region['postalCodeArea']['postalCodes'][0]['end'] );
					break;
				case '345678':
					$this->assertEquals( 'AU', $region['postalCodeArea']['regionCode'] );
					$this->assertCount( 1, $region['postalCodeArea']['postalCodes'] );
					$this->assertEquals( '9000', $region['postalCodeArea']['postalCodes'][0]['begin'] );
					$this->assertEquals( '9001', $region['postalCodeArea']['postalCodes'][0]['end'] );
					break;
				default:
					break;
			}
		}
	}

	public function test_sets_delivery_time_for_country() {
		$location_rate_1 = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );
		$location_rate_2 = new LocationRate( new ShippingLocation( 2, 'AU' ), new ShippingRate( 200 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_2 ] ),
				],
				'delivery_times'    => [
					'AU' => [
						'time'     => 5,
						'max_time' => 6,
					],
					'US' => [
						'time'     => 10,
						'max_time' => 10,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 2, $services );

		$us_services = array_filter(
			$services,
			function ( array $service ) {
				return 'US' === $service['deliveryCountries'][0];
			}
		);
		$us_service  = $us_services[ array_key_first( $us_services ) ];
		$this->assertEquals( 10, $us_service['deliveryTime']['minTransitDays'] );
		$this->assertEquals( 10, $us_service['deliveryTime']['maxTransitDays'] );

		$au_services = array_filter(
			$services,
			function ( array $service ) {
				return 'AU' === $service['deliveryCountries'][0];
			}
		);
		$au_service  = $au_services[ array_key_first( $au_services ) ];
		$this->assertEquals( 5, $au_service['deliveryTime']['minTransitDays'] );
		$this->assertEquals( 6, $au_service['deliveryTime']['maxTransitDays'] );
	}

	public function test_sets_the_currency_provided() {
		$location_rate_1 = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );
		$location_rate_2 = new LocationRate( new ShippingLocation( 2, 'AU' ), new ShippingRate( 200 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'EUR',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_2 ] ),
				],
				'delivery_times'    => [
					'AU' => [
						'time'     => 5,
						'max_time' => 6,
					],
					'US' => [
						'time'     => 10,
						'max_time' => 10,
					],
				],
			]
		);

		$this->assertCount( 2, $settings->get_services() );
		$this->assertEquals( 'EUR', $settings->get_services()[0]['currencyCode'] );
		$this->assertEquals( 'EUR', $settings->get_services()[1]['currencyCode'] );
	}

	public function test_fails_if_no_rates_collections_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);
	}

	public function test_fails_if_no_currency_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'rates_collections' => [ new CountryRatesCollection( 'US', [] ) ],
				'delivery_times'    => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);
	}

	public function test_fails_if_no_delivery_times_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [ new CountryRatesCollection( 'US', [] ) ],
			]
		);
	}

	public function test_fails_if_delivery_time_not_provided_for_country() {
		$this->expectException( InvalidValue::class );

		$location_rate_1 = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );
		$location_rate_2 = new LocationRate( new ShippingLocation( 2, 'AU' ), new ShippingRate( 200 ) );

		new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_2 ] ),
				],
				'delivery_times'    => [
					'AU' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);
	}

	public function test_fails_if_invalid_rates_collections_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'delivery_times'    => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'rates_collections' => [ new \stdClass() ],
			]
		);
	}
}
