<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\Location;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRatesProcessor;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRateFlat;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRateFree;
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
		$location       = new Location( 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRateFlat( 50 ) ),
			new LocationRate( $location, new ShippingRateFlat( 100 ) ),
			new LocationRate( $location, new ShippingRateFlat( 200 ) ),
		];
		$processed = $this->rates_processor->process( $location_rates );
		$this->assertEquals( 1, count( $processed ) );
		/** @var ShippingRateFlat $flat_rate */
		$flat_rate = $processed[0]->get_shipping_rate();
		$this->assertInstanceOf( ShippingRateFlat::class, $flat_rate );
		$this->assertEquals( 200, $flat_rate->get_rate() );
	}

	public function test_process_returns_only_highest_minimum_order_threshold_free_rate() {
		$location       = new Location( 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRateFree() ),
			new LocationRate( $location, new ShippingRateFree( 50 ) ),
			new LocationRate( $location, new ShippingRateFree( 100 ) ),
			new LocationRate( $location, new ShippingRateFree( 200 ) ),
		];
		$processed = $this->rates_processor->process( $location_rates );
		$this->assertEquals( 1, count( $processed ) );
		/** @var ShippingRateFree $free_rate */
		$free_rate = $processed[0]->get_shipping_rate();
		$this->assertInstanceOf( ShippingRateFree::class, $free_rate );
		$this->assertEquals( 200, $free_rate->get_threshold() );
	}

	public function test_process_returns_only_free_rate_if_it_has_no_threshold() {
		$location       = new Location( 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRateFree() ),
			new LocationRate( $location, new ShippingRateFlat( 200 ) ),
		];
		$processed = $this->rates_processor->process( $location_rates );
		$this->assertEquals( 1, count( $processed ) );
		/** @var ShippingRateFree $free_rate */
		$free_rate = $processed[0]->get_shipping_rate();
		$this->assertInstanceOf( ShippingRateFree::class, $free_rate );
	}

	public function test_process_returns_both_free_rate_and_flat_rate_if_free_rate_has_threshold() {
		$location       = new Location( 'US', 'CA' );
		$location_rates = [
			new LocationRate( $location, new ShippingRateFree() ),
			new LocationRate( $location, new ShippingRateFree( 50 ) ),
			new LocationRate( $location, new ShippingRateFlat( 200 ) ),
		];
		$processed      = $this->rates_processor->process( $location_rates );
		$this->assertEquals( 2, count( $processed ) );
		foreach ( $processed as $location_rate ) {
			if ( $location_rate->get_shipping_rate() instanceof ShippingRateFlat ) {
				/** @var ShippingRateFlat $flat_rate */
				$flat_rate = $location_rate->get_shipping_rate();
				$this->assertEquals( 200, $flat_rate->get_rate() );
			} else {
				/** @var ShippingRateFree $free_rate */
				$free_rate = $location_rate->get_shipping_rate();
				$this->assertEquals( 50, $free_rate->get_threshold() );
			}
		}
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->rates_processor = new LocationRatesProcessor();
	}
}
