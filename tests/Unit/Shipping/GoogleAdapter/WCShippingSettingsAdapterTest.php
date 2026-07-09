<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\PostcodesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\StatesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRegion;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\DeliveryTime;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\PostalCodeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Price;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Service as GoogleShippingService;

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

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertInstanceOf( PostcodesRateGroupAdapter::class, $services[0]->getRateGroups()[0] );
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

		$services = $settings->getServices();

		// The country without a shipping time is left out with an error naming
		// it; the remaining country's service still syncs, and the skipped
		// country's postcode list is not sent without its service.
		$this->assertCount( 1, $services );
		$this->assertEquals( 'US', $services[0]->getDeliveryCountry() );
		$this->assertCount( 0, $settings->getPostalCodeGroups() );
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

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertInstanceOf( StatesRateGroupAdapter::class, $services[0]->getRateGroups()[0] );
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

		$services = $settings->getServices();

		$this->assertCount( 3, $services );

		/** @var GoogleShippingService[] $min_order_services */
		$min_order_services = array_filter(
			$services,
			function ( GoogleShippingService $service ) {
				return null !== $service->getMinimumOrderValue();
			}
		);
		$this->assertCount( 1, $min_order_services );

		$min_order_service = $min_order_services[ array_key_first( $min_order_services ) ];
		$this->assertEquals( 'US', $min_order_service->getDeliveryCountry() );
		$this->assertInstanceOf( Price::class, $min_order_service->getMinimumOrderValue() );
		$this->assertEquals( 1000, $min_order_service->getMinimumOrderValue()->getValue() );
		$this->assertEquals( 'USD', $min_order_service->getMinimumOrderValue()->getCurrency() );
	}

	public function test_sets_postcode_groups() {
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

		$postcode_groups = $settings->getPostalCodeGroups();

		$this->assertCount( 3, $postcode_groups );

		$postcode_names = array_map(
			function ( PostalCodeGroup $postal_code_group ) {
				return $postal_code_group->getName();
			},
			$postcode_groups
		);
		$this->assertEqualSets(
			[
				'123456',
				'234567',
				'345678',
			],
			$postcode_names
		);

		foreach ( $postcode_groups as $postal_code_group ) {
			switch ( $postal_code_group->getName() ) {
				case '123456':
					$this->assertEquals( 'US', $postal_code_group->getCountry() );
					$this->assertCount( 2, $postal_code_group->getPostalCodeRanges() );
					foreach ( $postal_code_group->getPostalCodeRanges() as $postal_code_range ) {
						if ( '2000' === $postal_code_range->getPostalCodeRangeBegin() ) {
							$this->assertEquals( '2001', $postal_code_range->getPostalCodeRangeEnd() );
						} else {
							$this->assertEquals( '1000', $postal_code_range->getPostalCodeRangeBegin() );
						}
					}
					break;
				case '234567':
					$this->assertEquals( 'US', $postal_code_group->getCountry() );
					$this->assertCount( 1, $postal_code_group->getPostalCodeRanges() );
					$this->assertEquals( '9000', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeBegin() );
					$this->assertEquals( '9001', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeEnd() );
					break;
				case '345678':
					$this->assertEquals( 'AU', $postal_code_group->getCountry() );
					$this->assertCount( 1, $postal_code_group->getPostalCodeRanges() );
					$this->assertEquals( '9000', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeBegin() );
					$this->assertEquals( '9001', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeEnd() );
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

		$services = $settings->getServices();

		$this->assertCount( 2, $services );

		/** @var GoogleShippingService[] $us_services */
		$us_services = array_filter(
			$services,
			function ( GoogleShippingService $service ) {
				return 'US' === $service->getDeliveryCountry();
			}
		);
		$us_service  = $us_services[ array_key_first( $us_services ) ];
		$this->assertInstanceOf( DeliveryTime::class, $us_service->getDeliveryTime() );
		$this->assertEquals( 10, $us_service->getDeliveryTime()->getMinTransitTimeInDays() );
		$this->assertEquals( 10, $us_service->getDeliveryTime()->getMaxTransitTimeInDays() );

		/** @var GoogleShippingService[] $au_services */
		$au_services = array_filter(
			$services,
			function ( GoogleShippingService $service ) {
				return 'AU' === $service->getDeliveryCountry();
			}
		);
		$au_service  = $au_services[ array_key_first( $au_services ) ];
		$this->assertInstanceOf( DeliveryTime::class, $au_service->getDeliveryTime() );
		$this->assertEquals( 5, $au_service->getDeliveryTime()->getMinTransitTimeInDays() );
		$this->assertEquals( 6, $au_service->getDeliveryTime()->getMaxTransitTimeInDays() );
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

		$this->assertCount( 2, $settings->getServices() );
		$this->assertEquals( 'EUR', $settings->getServices()[0]->getCurrency() );
		$this->assertEquals( 'EUR', $settings->getServices()[1]->getCurrency() );
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

		$services   = $settings->getServices();
		$by_country = [];
		foreach ( $services as $service ) {
			$by_country[ $service->getDeliveryCountry() ][] = $service;
		}

		$this->assertNotEmpty( $by_country['US'] );
		$this->assertNotEmpty( $by_country['FR'] );

		foreach ( $by_country['US'] as $service ) {
			$this->assertEquals( 'USD', $service->getCurrency() );
			if ( $service->getMinimumOrderValue() ) {
				$this->assertEquals( 'USD', $service->getMinimumOrderValue()->getCurrency() );
			}
		}

		foreach ( $by_country['FR'] as $service ) {
			$this->assertEquals( 'EUR', $service->getCurrency() );
			if ( $service->getMinimumOrderValue() ) {
				$this->assertEquals( 'EUR', $service->getMinimumOrderValue()->getCurrency() );
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

		foreach ( $settings->getServices() as $service ) {
			$expected_currency = 'FR' === $service->getDeliveryCountry() ? 'EUR' : 'USD';

			foreach ( $service->getRateGroups() as $rate_group ) {
				if ( $rate_group->getSingleValue() ) {
					$this->assertEquals(
						$expected_currency,
						$rate_group->getSingleValue()->getFlatRate()->getCurrency()
					);
				}

				if ( $rate_group->getMainTable() ) {
					foreach ( $rate_group->getMainTable()->getRows() as $row ) {
						foreach ( $row->getCells() as $cell ) {
							$this->assertEquals(
								$expected_currency,
								$cell->getFlatRate()->getCurrency()
							);
						}
					}
				}
			}
		}
	}
}
