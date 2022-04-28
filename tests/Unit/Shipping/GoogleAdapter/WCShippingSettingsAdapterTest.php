<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\CountryPostcodesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\StatesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Google\Service\ShoppingContent\DeliveryTime;
use Google\Service\ShoppingContent\PostalCodeGroup;
use Google\Service\ShoppingContent\Price;
use Google\Service\ShoppingContent\Service as GoogleShippingService;

/**
 * Class WCShippingSettingsAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter
 */
class WCShippingSettingsAdapterTest extends UnitTest {
	public function test_creates_rate_group_for_country_postal_rates() {
		$location_1      = new ShippingLocation( 1, 'US', null, [ new PostcodeRange( '1000' ) ] );
		$location_rate_1 = new LocationRate( $location_1, new ShippingRate( 100 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency'          => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
				],
				'delivery_times'    => [ 'US' => 2 ],
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertInstanceOf( CountryPostcodesRateGroupAdapter::class, $services[0]->getRateGroups()[0] );
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
				'delivery_times'    => [ 'US' => 2 ],
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
				'currency' => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1, $location_rate_2 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_3 ] ),
				],
				'delivery_times' => [ 'AU' => 1, 'US' => 2 ],
			]
		);

		$services = $settings->getServices();

		$this->assertCount(3, $services);

		/* @var GoogleShippingService[] $min_order_services **/
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
		$location_1      = new ShippingLocation(
			1,
			'US',
			null,
			[
				new PostcodeRange( '1000' ),
				new PostcodeRange( '2000', '2001' ),
			]
		);
		$location_rate_1 = new LocationRate( $location_1, new ShippingRate( 100 ) );

		$location_2      = new ShippingLocation( 2, 'US', 'CA', [ new PostcodeRange( '9000', '9001' ) ] );
		$location_rate_2 = new LocationRate( $location_2, new ShippingRate( 200 ) );

		$location_3      = new ShippingLocation( 3, 'AU', 'NSW', [ new PostcodeRange( '9000', '9001' ) ] );
		$location_rate_3 = new LocationRate( $location_3, new ShippingRate( 300 ) );

		$settings = new WCShippingSettingsAdapter(
			[
				'currency' => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1, $location_rate_2 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_3 ] ),
				],
				'delivery_times' => [ 'AU' => 1, 'US' => 2 ],
			]
		);

		$postcode_groups = $settings->getPostalCodeGroups();

		$this->assertCount( 3, $postcode_groups );

		$postcode_names = array_map(
			function (PostalCodeGroup $postal_code_group) {
				return $postal_code_group->getName();
			},
			$postcode_groups
		);
		$this->assertEqualSets(
			[
				'US - 1000,2000...2001',
				'US - 9000...9001',
				'AU - 9000...9001',
			],
			$postcode_names
		);

		foreach ( $postcode_groups as $postal_code_group ) {
			switch ( $postal_code_group->getName() ) {
				case 'US - 1000,2000...2001':
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
				case 'US - 9000...9001':
					$this->assertEquals( 'US', $postal_code_group->getCountry() );
					$this->assertCount( 1, $postal_code_group->getPostalCodeRanges() );
					$this->assertEquals( '9000', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeBegin() );
					$this->assertEquals( '9001', $postal_code_group->getPostalCodeRanges()[0]->getPostalCodeRangeEnd() );
					break;
				case 'AU - 9000...9001':
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
				'currency' => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_2 ] ),
				],
				'delivery_times' => [ 'AU' => 5, 'US' => 10 ],
			]
		);

		$services = $settings->getServices();

		$this->assertCount(2, $services);

		/* @var GoogleShippingService[] $us_services **/
		$us_services = array_filter(
			$services,
			function ( GoogleShippingService $service ) {
				return 'US' === $service->getDeliveryCountry();
			}
		);
		$us_service = $us_services[ array_key_first( $us_services ) ];
		$this->assertInstanceOf( DeliveryTime::class, $us_service->getDeliveryTime() );
		$this->assertEquals( 10, $us_service->getDeliveryTime()->getMinTransitTimeInDays() );
		$this->assertEquals( 10, $us_service->getDeliveryTime()->getMaxTransitTimeInDays() );

		/* @var GoogleShippingService[] $au_services * */
		$au_services = array_filter(
			$services,
			function ( GoogleShippingService $service ) {
				return 'AU' === $service->getDeliveryCountry();
			}
		);
		$au_service  = $au_services[ array_key_first( $au_services ) ];
		$this->assertInstanceOf( DeliveryTime::class, $au_service->getDeliveryTime() );
		$this->assertEquals( 5, $au_service->getDeliveryTime()->getMinTransitTimeInDays() );
		$this->assertEquals( 5, $au_service->getDeliveryTime()->getMaxTransitTimeInDays() );
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
				'delivery_times'    => [ 'AU' => 5, 'US' => 10 ],
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
				'currency' => 'USD',
				'delivery_times' => [ 'US' => 1 ],
			]
		);
	}

	public function test_fails_if_no_currency_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'rates_collections' => [ new CountryRatesCollection( 'US', [] ) ],
				'delivery_times' => [ 'US' => 1 ],
			]
		);
	}

	public function test_fails_if_no_delivery_times_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'currency' => 'USD',
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
				'currency' => 'USD',
				'rates_collections' => [
					new CountryRatesCollection( 'US', [ $location_rate_1 ] ),
					new CountryRatesCollection( 'AU', [ $location_rate_2 ] ),
				],
				'delivery_times' => [ 'AU' => 1 ],
			]
		);
	}

	public function test_fails_if_invalid_rates_collections_provided() {
		$this->expectException( InvalidValue::class );

		new WCShippingSettingsAdapter(
			[
				'currency' => 'USD',
				'delivery_times' => [ 'US' => 1 ],
				'rates_collections' => [ new \stdClass() ],
			]
		);
	}
}
