<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRatesProcessor;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class LocationRatesProcessorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property LocationRatesProcessor $rates_processor
 */
class LocationRatesProcessorTest extends UnitTest {

	public function test_process_returns_only_most_expensive_flat_rate() {
		$location       = new ShippingLocation( 21137, 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRate( 50 ) ),
			new LocationRate( $location, new ShippingRate( 100 ) ),
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 1, $processed );
		$this->assertEquals( 200, $processed[0]->get_shipping_rate()->get_rate() );
	}

	public function test_process_returns_only_highest_min_order_amount_free_rate() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_2 = new ShippingRate( 0 );
		$free_rate_2->set_min_order_amount( 50 );
		$free_rate_3 = new ShippingRate( 0 );
		$free_rate_3->set_min_order_amount( 100 );
		$free_rate_4 = new ShippingRate( 0 );
		$free_rate_4->set_min_order_amount( 200 );

		$location_rates = [
			new LocationRate( $location, new ShippingRate( 100 ) ), // We need a non-free rate so that the free rates are not ignored.
			new LocationRate( $location, $free_rate_1 ),
			new LocationRate( $location, $free_rate_2 ),
			new LocationRate( $location, $free_rate_3 ),
			new LocationRate( $location, $free_rate_4 ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 2, $processed );
		foreach ( $processed as $location_rate ) {
			if ( $location_rate->get_shipping_rate()->is_free() ) {
				$this->assertEquals( 200, $location_rate->get_shipping_rate()->get_min_order_amount() );
			}
		}
	}

	public function test_process_returns_most_expensive_rate_even_if_free_rate_exists_with_no_min_order_amount() {
		$location       = new ShippingLocation( 21137, 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRate( 0 ) ),
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 1, $processed );
		$this->assertEquals( 200, $processed[0]->get_shipping_rate()->get_rate() );
	}

	public function test_process_returns_both_free_rate_and_flat_rate_if_free_rate_has_min_order_amount() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_1->set_min_order_amount( 50 );

		$location_rates = [
			new LocationRate( $location, new ShippingRate( 0 ) ),
			new LocationRate( $location, $free_rate_1 ),
			new LocationRate( $location, new ShippingRate( 200 ) ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 2, $processed );
		foreach ( $processed as $location_rate ) {
			if ( $location_rate->get_shipping_rate()->is_free() ) {
				$this->assertEquals( 50, $location_rate->get_shipping_rate()->get_min_order_amount() );
			} else {
				$this->assertEquals( 200, $location_rate->get_shipping_rate()->get_rate() );
			}
		}
	}

	public function test_process_returns_no_rates_if_there_is_only_a_free_rate_with_min_order_amount() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_1->set_min_order_amount( 50 );

		$location_rates = [
			new LocationRate( $location, $free_rate_1 ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertEmpty( $processed );
	}

	public function test_process_returns_free_rate_if_there_is_both_unconditional_free_rate_and_a_free_rate_with_min_order_amount() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$free_rate_1 = new ShippingRate( 0 );
		$free_rate_1->set_min_order_amount( 50 );

		$location_rates = [
			new LocationRate( $location, new ShippingRate( 0 ) ),
			new LocationRate( $location, $free_rate_1 ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 1, $processed );
		$this->assertEquals( 0, $processed[0]->get_shipping_rate()->get_rate() );
		$this->assertNull( $processed[0]->get_shipping_rate()->get_min_order_amount() );
	}

	public function test_process_returns_flat_rates_with_shipping_classes() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$light_rate = new ShippingRate( 10 );
		$light_rate->set_applicable_classes( [ 'light' ] );
		$heavy_rate = new ShippingRate( 20 );
		$heavy_rate->set_applicable_classes( [ 'heavy' ] );

		$location_rates = [
			new LocationRate( $location, new ShippingRate( 15 ) ),
			new LocationRate( $location, $light_rate ),
			new LocationRate( $location, $heavy_rate ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 3, $processed );
	}

	public function test_process_returns_most_expensive_flat_rates_with_shipping_classes() {
		$location = new ShippingLocation( 21137, 'US', 'CA' );

		$light_rate = new ShippingRate( 10 );
		$light_rate->set_applicable_classes( [ 'light' ] );
		$light_rate_2 = new ShippingRate( 20 );
		$light_rate_2->set_applicable_classes( [ 'light' ] );

		$location_rates = [
			new LocationRate( $location, $light_rate ),
			new LocationRate( $location, $light_rate_2 ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertCount( 1, $processed );
		$this->assertEquals( 20, $processed[0]->get_shipping_rate()->get_rate() );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		$this->rates_processor = new LocationRatesProcessor();
	}
}
