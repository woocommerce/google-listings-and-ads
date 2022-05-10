<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ServiceRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class CountryRatesCollectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 */
class CountryRatesCollectionTest extends UnitTest {

	public function test_returns_newly_added_location_rates() {
		$location_rates = [
			new LocationRate( new ShippingLocation( 0, 'US' ), new ShippingRate( 500 ) ),
		];

		$collection = new CountryRatesCollection( 'US', $location_rates );

		$this->assertCount( 1, $collection->get_rates_grouped_by_service() );
		$this->assertCount( 1, $collection->get_rates_grouped_by_service()[0]->get_location_rates() );

		$min_order_rate = new ShippingRate( 0 );
		$min_order_rate->set_min_order_amount( 1000 );
		$collection->add_location_rate( new LocationRate( new ShippingLocation( 0, 'US' ), $min_order_rate ) );
		$this->assertCount( 2, $collection->get_rates_grouped_by_service() );
		$this->assertCount( 1, $collection->get_rates_grouped_by_service()[0]->get_location_rates() );
		$this->assertCount( 1, $collection->get_rates_grouped_by_service()[1]->get_location_rates() );

		$collection->set_location_rates( [ new LocationRate( new ShippingLocation( 1, 'US' ), new ShippingRate( 200 ) ) ] );
		$this->assertCount( 1, $collection->get_rates_grouped_by_service() );
		$this->assertCount( 1, $collection->get_rates_grouped_by_service()[0]->get_location_rates() );
	}

	public function test_returns_rates_grouped_by_service() {
		$min_order_rate = new ShippingRate( 0 );
		$min_order_rate->set_min_order_amount( 1000 );
		$location_rates = [
			// Country level
			new LocationRate( new ShippingLocation( 0, 'US' ), $min_order_rate ),
			new LocationRate( new ShippingLocation( 0, 'US' ), new ShippingRate( 500 ) ),
			// State level
			new LocationRate( new ShippingLocation( 1, 'US', 'CA' ), new ShippingRate( 100 ) ),
			new LocationRate( new ShippingLocation( 1, 'US', 'CA' ), new ShippingRate( 0 ) ),
			// Country post code level
			new LocationRate( new ShippingLocation( 0, 'US', null, [ new PostcodeRange( '4000', '4001' ) ] ), new ShippingRate( 110 ) ),
		];

		$collection = new CountryRatesCollection( 'US', $location_rates );

		$groups = $collection->get_rates_grouped_by_service();
		$this->assertCount( 4, $groups );
		$this->assertContainsOnlyInstancesOf( ServiceRatesCollection::class, $groups );
	}

	public function test_returns_rates_applicable_to_both_states_and_postcode_under_same_service_as_country_postcodes() {
		$location_rates = [
			// Country post code level
			new LocationRate( new ShippingLocation( 0, 'US', null, [ new PostcodeRange( '4000', '4001' ) ] ), new ShippingRate( 110 ) ),
			// State post code level
			new LocationRate( new ShippingLocation( 1, 'US', 'CA', [ new PostcodeRange( '9000', '9001' ) ] ), new ShippingRate( 10 ) ),
			new LocationRate( new ShippingLocation( 1, 'US', 'NV', [ new PostcodeRange( '2000', '2001' ) ] ), new ShippingRate( 10 ) ),
		];

		$collection = new CountryRatesCollection( 'US', $location_rates );

		$groups = $collection->get_rates_grouped_by_service();
		$this->assertCount( 1, $groups );
		$this->assertContainsOnlyInstancesOf( ServiceRatesCollection::class, $groups );
		$this->assertCount( 3, $groups[0]->get_location_rates() );
	}

	public function test_returns_separate_service_for_rates_with_min_order_amount() {
		$min_order_rate = new ShippingRate( 0 );
		$min_order_rate->set_min_order_amount( 1000 );
		$location_rates = [
			new LocationRate( new ShippingLocation( 0, 'US' ), $min_order_rate ),
			new LocationRate( new ShippingLocation( 0, 'US' ), new ShippingRate( 500 ) ),
		];

		$collection = new CountryRatesCollection( 'US', $location_rates );

		$groups = $collection->get_rates_grouped_by_service();
		$this->assertCount( 2, $groups );
		$this->assertContainsOnlyInstancesOf( ServiceRatesCollection::class, $groups );
		// Each group must have one location rate.
		$this->assertCount( 1, $groups[0]->get_location_rates() );
		$this->assertCount( 1, $groups[1]->get_location_rates() );
		foreach ( $groups as $service_collection ) {
			if ( 0.0 === $service_collection->get_location_rates()[0]->get_shipping_rate()->get_rate() ) {
				$this->assertEquals( 1000, $service_collection->get_min_order_amount() );
			} else {
				$this->assertNull( $service_collection->get_min_order_amount() );
			}
		}
	}

	public function test_fails_if_different_country_rates_provided() {
		$location_rates = [
			new LocationRate( new ShippingLocation( 0, 'US' ), new ShippingRate( 500 ) ),
			new LocationRate( new ShippingLocation( 0, 'AU' ), new ShippingRate( 100 ) ),
		];

		$this->expectException( InvalidValue::class );

		new CountryRatesCollection( 'US', $location_rates );
	}
}
