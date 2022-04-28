<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class DBShippingSettingsAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter
 */
class DBShippingSettingsAdapterTest extends UnitTest {
	public function test_maps_db_rates() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 10,
				'options' => [],
			],
			[
				'country' => 'AU',
				'rate'    => 50,
				'options' => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [ 'US' => 1, 'AU' => 2 ],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 2, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertCount( 1, $services[1]->getRateGroups() );

		foreach ( $services as $service ) {
			// Assert that the delivery country of both services is either US or AU
			$this->assertTrue( in_array( $service->getDeliveryCountry(), [ 'US', 'AU' ], true ) );

			if ( 'US' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'USD', $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency() );
				$this->assertEquals( 10, $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue() );
			} elseif ( 'AU' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'USD', $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency() );
				$this->assertEquals( 50, $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue() );
			}
		}
	}

	public function test_ignores_negative_rates() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => -10,
				'options' => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [ 'US' => 1 ],
				'db_rates'       => $db_rates,
			]
		);

		$this->assertEmpty(  $settings->getServices() );
	}

	public function test_sets_free_shipping_threshold_on_free_rates() {
		$db_rates = [
			[
				'country' => 'US',
				'rate' => 0,
				'options' => [
					'free_shipping_threshold' => 100,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [ 'US' => 1 ],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertEquals( 100, $services[0]->getMinimumOrderValue()->getValue() );
		$this->assertCount( 1, $services[0]->getRateGroups() );
	}

	public function test_creates_separate_service_for_free_shipping_threshold() {
		$db_rates = [
			[
				'country' => 'US',
				'rate' => 10.0,
				'options' => [
					'free_shipping_threshold' => 100.0,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [ 'US' => 1 ],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 2, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertCount( 1, $services[1]->getRateGroups() );

		foreach ( $services as $service ) {
			if ( 0.0 === (float) $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue() ) {
				$this->assertEquals( 100, $service->getMinimumOrderValue()->getValue() );
			} else {
				$this->assertNull( $service->getMinimumOrderValue() );
			}
		}
	}

	public function test_fails_if_no_db_rates_provided() {
		$this->expectException( InvalidValue::class );

		new DBShippingSettingsAdapter(
			[
				'currency' => 'USD',
				'delivery_times' => [ 'US' => 1 ],
			]
		);
	}

	public function test_fails_if_no_currency_provided() {
		$this->expectException( InvalidValue::class );

		new DBShippingSettingsAdapter(
			[
				'delivery_times' => [ 'US' => 1 ],
				'db_rates' => [
					[
						'country' => 'US',
						'rate' => 10.0,
						'options' => [],
					],
				],
			]
		);
	}

	public function test_fails_if_no_delivery_times_provided() {
		$this->expectException( InvalidValue::class );

		new DBShippingSettingsAdapter(
			[
				'currency' => 'USD',
				'db_rates' => [
					[
						'country' => 'US',
						'rate' => 10.0,
						'options' => [],
					],
				],
			]
		);
	}
}
