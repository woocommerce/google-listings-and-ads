<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ServiceRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class ServiceRatesCollectionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 */
class ServiceRatesCollectionTest extends UnitTest {
	public function test_get_rates_grouped_by_shipping_class() {
		$location = new ShippingLocation( 1, 'US' );

		$light_rate = new ShippingRate( 100 );
		$light_rate->set_applicable_classes( [ 'light' ] );
		$location_rate_1 = new LocationRate( $location, $light_rate );

		$heavy_rate = new ShippingRate( 200 );
		$heavy_rate->set_applicable_classes( [ 'heavy' ] );
		$location_rate_2 = new LocationRate( $location, $heavy_rate );

		$service_collection = new ServiceRatesCollection(
			'US',
			ShippingLocation::COUNTRY_AREA,
			null,
			[
				$location_rate_1,
				$location_rate_2,
			]
		);

		$class_rates = $service_collection->get_rates_grouped_by_shipping_class();

		$this->assertCount( 2, $class_rates );

		$this->assertArrayHasKey( 'light', $class_rates );
		$this->assertArrayHasKey( 'heavy', $class_rates );

		$this->assertContainsOnlyInstancesOf( LocationRate::class, $class_rates['light'] );
		$this->assertContainsOnlyInstancesOf( LocationRate::class, $class_rates['heavy'] );

		foreach ( $class_rates as $class => $location_rates ) {
			foreach ( $location_rates as $location_rate ) {
				$this->assertTrue( in_array( $class, $location_rate->get_shipping_rate()->get_applicable_classes(), true ) );
			}
		}
	}
}
