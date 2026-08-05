<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
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

	public function test_skips_country_without_delivery_time_and_reports_error() {
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

		$location_rate_us = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );

		// The skipped country's rate is limited to a postcode region, proving
		// its postcode list is excluded along with its prices.
		$au_region        = new ShippingRegion( '654321', 'AU', [ new PostcodeRange( '2000' ) ] );
		$location_rate_au = new LocationRate( new ShippingLocation( 2, 'AU', null, $au_region ), new ShippingRate( 50 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_us ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_au ] ),
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

		// The country without a shipping time is left out with an error naming
		// it; the remaining country's service still syncs, and the skipped
		// country's postcode region is not sent without its service.
		$this->assertCount( 1, $services );
		$this->assertEquals( 'US', $services[0]['deliveryCountries'][0] );
		$this->assertCount( 0, $settings->get_regions() );
		$this->assertCount( 1, $reported );
		$this->assertStringContainsString( 'AU', $reported[0] );
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

	public function test_country_currency_map_overrides_per_service_currency() {
		$min_order_rate = new ShippingRate( 0 );
		$min_order_rate->set_min_order_amount( 1000 );

		$us_rate = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );
		$fr_rate = new LocationRate( new ShippingLocation( 2, 'FR' ), new ShippingRate( 50 ) );
		$fr_min  = new LocationRate( new ShippingLocation( 2, 'FR' ), $min_order_rate );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'FR' => 'EUR',
				],
				'wpml'                 => $this->create_wpml_doubling_converter(),
				'rates_collections'    => [
					new CountryRatesCollection( 'US', [ $us_rate ] ),
					new CountryRatesCollection( 'FR', [ $fr_rate, $fr_min ] ),
				],
				'delivery_times'       => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
					'FR' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services   = $settings->get_services();
		$by_country = [];
		foreach ( $services as $service ) {
			$by_country[ $service['deliveryCountries'][0] ][] = $service;
		}

		$this->assertNotEmpty( $by_country['US'] );
		$this->assertNotEmpty( $by_country['FR'] );

		foreach ( $by_country['US'] as $service ) {
			$this->assertEquals( 'USD', $service['currencyCode'] );
			if ( isset( $service['minimumOrderValue'] ) ) {
				$this->assertEquals( 'USD', $service['minimumOrderValue']['currencyCode'] );
			}
		}

		foreach ( $by_country['FR'] as $service ) {
			$this->assertEquals( 'EUR', $service['currencyCode'] );
			if ( isset( $service['minimumOrderValue'] ) ) {
				$this->assertEquals( 'EUR', $service['minimumOrderValue']['currencyCode'] );
			}
		}
	}

	public function test_rate_group_prices_use_the_service_currency_for_overridden_countries() {
		$fr_country_rate = new LocationRate( new ShippingLocation( 2, 'FR' ), new ShippingRate( 50 ) );

		$fr_region        = new ShippingRegion( '654321', 'FR', [ new PostcodeRange( '75000' ) ] );
		$fr_postcode_rate = new LocationRate( new ShippingLocation( 2, 'FR', null, $fr_region ), new ShippingRate( 60 ) );

		$fr_state_rate = new LocationRate( new ShippingLocation( 2, 'FR', 'IDF' ), new ShippingRate( 70 ) );

		$us_country_rate = new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 100 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'FR' => 'EUR',
				],
				'wpml'                 => $this->create_wpml_doubling_converter(),
				'rates_collections'    => [
					new CountryRatesCollection( 'US', [ $us_country_rate ] ),
					new CountryRatesCollection( 'FR', [ $fr_country_rate, $fr_postcode_rate, $fr_state_rate ] ),
				],
				'delivery_times'       => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
					'FR' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		foreach ( $settings->get_services() as $service ) {
			$expected_currency = 'FR' === $service['deliveryCountries'][0] ? 'EUR' : 'USD';

			foreach ( $service['rateGroups'] as $rate_group ) {
				if ( isset( $rate_group['singleValue'] ) ) {
					$this->assertEquals(
						$expected_currency,
						$rate_group['singleValue']['flatRate']['currencyCode']
					);

					// Non-store-currency amounts are the converted values, not
					// the store-currency amounts relabelled: 50 USD doubles to
					// 100 EUR under the test converter.
					if ( 'EUR' === $expected_currency ) {
						$this->assertSame( '100000000', $rate_group['singleValue']['flatRate']['amountMicros'] );
					}
				}

				if ( isset( $rate_group['mainTable'] ) ) {
					foreach ( $rate_group['mainTable']['rows'] as $row ) {
						foreach ( $row['cells'] as $cell ) {
							$this->assertEquals(
								$expected_currency,
								$cell['flatRate']['currencyCode']
							);
						}
					}
				}
			}
		}
	}

	public function test_creates_one_service_per_currency_for_a_multi_currency_country() {
		$ae_rate = new LocationRate( new ShippingLocation( 1, 'AE' ), new ShippingRate( 50 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'AE' => [ 'USD', 'AED' ],
				],
				'wpml'                 => $this->create_wpml_doubling_converter(),
				'rates_collections'    => [
					new CountryRatesCollection( 'AE', [ $ae_rate ] ),
				],
				'delivery_times'       => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 2, $services );

		$by_currency = [];
		foreach ( $services as $service ) {
			$by_currency[ $service['currencyCode'] ] = $service;
			$this->assertEquals( 'AE', $service['deliveryCountries'][0] );
		}

		// The store-currency service keeps the WooCommerce amount; the AED
		// service carries the converted amount (50 doubled to 100).
		$this->assertSame( '50000000', $by_currency['USD']['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
		$this->assertSame( '100000000', $by_currency['AED']['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );

		// Service names must be unique within the Merchant Center account.
		$this->assertNotEquals( $by_currency['USD']['serviceName'], $by_currency['AED']['serviceName'] );
	}

	public function test_fixed_exchange_rate_produces_a_service_without_wpml_conversion() {
		$ae_rate = new LocationRate( new ShippingLocation( 1, 'AE' ), new ShippingRate( 50 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'               => 'USD',
				'country_currency_map'   => [
					'AE' => [ 'AED' ],
				],
				'country_exchange_rates' => [
					'AE' => 3.67,
				],
				'rates_collections'      => [
					new CountryRatesCollection( 'AE', [ $ae_rate ] ),
				],
				'delivery_times'         => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( 'AED', $services[0]['currencyCode'] );
		$this->assertSame( '183500000', $services[0]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
	}

	public function test_fixed_rate_is_used_when_wpml_is_present_but_cannot_convert() {
		$ae_rate = new LocationRate( new ShippingLocation( 1, 'AE' ), new ShippingRate( 50 ) );

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'convert_amount' )->willReturn( null );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'               => 'USD',
				'country_currency_map'   => [
					'AE' => [ 'AED' ],
				],
				'country_exchange_rates' => [
					'AE' => 3.67,
				],
				'wpml'                   => $wpml,
				'rates_collections'      => [
					new CountryRatesCollection( 'AE', [ $ae_rate ] ),
				],
				'delivery_times'         => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( 'AED', $services[0]['currencyCode'] );
		$this->assertSame( '183500000', $services[0]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
	}

	public function test_wpml_conversion_still_wins_over_the_fixed_rate() {
		// An unchanged market: WPML is available, so the configured rate is not consulted.
		$ae_rate = new LocationRate( new ShippingLocation( 1, 'AE' ), new ShippingRate( 50 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'               => 'USD',
				'country_currency_map'   => [
					'AE' => [ 'AED' ],
				],
				'country_exchange_rates' => [
					'AE' => 3.67,
				],
				'wpml'                   => $this->create_wpml_doubling_converter(),
				'rates_collections'      => [
					new CountryRatesCollection( 'AE', [ $ae_rate ] ),
				],
				'delivery_times'         => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( 100.0, $services[0]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] / 1000000 );
	}

	public function test_leaves_out_non_store_currency_service_when_conversion_unavailable() {
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

		$ae_rate = new LocationRate( new ShippingLocation( 1, 'AE' ), new ShippingRate( 50 ) );

		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'convert_amount' )->willReturn( null );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'AE' => [ 'USD', 'AED' ],
				],
				'wpml'                 => $wpml,
				'rates_collections'    => [
					new CountryRatesCollection( 'AE', [ $ae_rate ] ),
				],
				'delivery_times'       => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
			]
		);

		$services = $settings->get_services();

		// The store-currency service still syncs; the unconvertible AED
		// service is left out with an error naming the country and currency.
		$this->assertCount( 1, $services );
		$this->assertEquals( 'USD', $services[0]['currencyCode'] );
		$this->assertCount( 1, $reported );
		$this->assertStringContainsString( 'AED', $reported[0] );
		$this->assertStringContainsString( 'AE', $reported[0] );
	}

	/**
	 * Returns a WPML mock whose convert_amount() doubles the amount, making
	 * converted values visible in assertions.
	 *
	 * @return WPML
	 */
	private function create_wpml_doubling_converter(): WPML {
		$wpml = $this->createMock( WPML::class );
		$wpml->method( 'convert_amount' )->willReturnCallback(
			static function ( float $amount ): float {
				return $amount * 2;
			}
		);

		return $wpml;
	}
}
