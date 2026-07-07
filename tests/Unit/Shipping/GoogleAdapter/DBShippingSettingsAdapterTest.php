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
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 3,
					],
					'AU' => [
						'time'     => 2,
						'max_time' => 4,
					],
				],
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
				$this->assertEquals( 1, $service->getDeliveryTime()->getMinTransitTimeInDays() );
				$this->assertEquals( 3, $service->getDeliveryTime()->getMaxTransitTimeInDays() );
			} elseif ( 'AU' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'USD', $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency() );
				$this->assertEquals( 50, $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue() );
				$this->assertEquals( 2, $service->getDeliveryTime()->getMinTransitTimeInDays() );
				$this->assertEquals( 4, $service->getDeliveryTime()->getMaxTransitTimeInDays() );
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
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$this->assertEmpty( $settings->getServices() );
	}

	public function test_sets_free_shipping_threshold_on_free_rates() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 0,
				'options' => [
					'free_shipping_threshold' => 100,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertEquals( 100, $services[0]->getMinimumOrderValue()->getValue() );
		$this->assertCount( 1, $services[0]->getRateGroups() );
	}

	public function test_creates_zero_flat_rate_for_free_shipping_no_threshold() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 0,
				'options' => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]->getRateGroups() );
		$this->assertEquals( 0, $services[0]->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue() );
	}

	public function test_creates_separate_service_for_free_shipping_threshold() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 10.0,
				'options' => [
					'free_shipping_threshold' => 100.0,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
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

		new DBShippingSettingsAdapter(
			[
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'       => [
					[
						'country' => 'US',
						'rate'    => 10.0,
						'options' => [],
					],
				],
			]
		);
	}

	public function test_uses_per_row_currency_for_mixed_currency_rates() {
		$db_rates = [
			[
				'country'  => 'US',
				'currency' => 'USD',
				'rate'     => 10,
				'options'  => [],
			],
			[
				'country'  => 'FR',
				'currency' => 'EUR',
				'rate'     => 8,
				'options'  => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 3,
					],
					'FR' => [
						'time'     => 2,
						'max_time' => 5,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 2, $services );

		foreach ( $services as $service ) {
			$rate_currency = $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency();

			if ( 'US' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'USD', $service->getCurrency() );
				$this->assertEquals( 'USD', $rate_currency );
			} elseif ( 'FR' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'EUR', $service->getCurrency() );
				$this->assertEquals( 'EUR', $rate_currency );
			}
		}
	}

	public function test_falls_back_to_adapter_currency_when_row_currency_missing() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 10,
				'options' => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'GBP',
				'delivery_times' => [
					'US' => [
						'time'     => 1,
						'max_time' => 3,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertEquals( 'GBP', $services[0]->getCurrency() );
		$this->assertEquals(
			'GBP',
			$services[0]->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency()
		);
	}

	public function test_minimum_order_value_uses_row_currency_for_free_rate() {
		$db_rates = [
			[
				'country'  => 'FR',
				'currency' => 'EUR',
				'rate'     => 0,
				'options'  => [
					'free_shipping_threshold' => 75,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'FR' => [
						'time'     => 2,
						'max_time' => 5,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 1, $services );
		$this->assertEquals( 75, $services[0]->getMinimumOrderValue()->getValue() );
		$this->assertEquals( 'EUR', $services[0]->getMinimumOrderValue()->getCurrency() );
	}

	public function test_conditional_free_shipping_service_uses_row_currency() {
		$db_rates = [
			[
				'country'  => 'FR',
				'currency' => 'EUR',
				'rate'     => 5.0,
				'options'  => [
					'free_shipping_threshold' => 100.0,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'       => 'USD',
				'delivery_times' => [
					'FR' => [
						'time'     => 2,
						'max_time' => 5,
					],
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 2, $services );

		foreach ( $services as $service ) {
			$rate_value = (float) $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getValue();
			$this->assertEquals( 'EUR', $service->getCurrency() );

			if ( 0.0 === $rate_value ) {
				$this->assertEquals( 100, $service->getMinimumOrderValue()->getValue() );
				$this->assertEquals( 'EUR', $service->getMinimumOrderValue()->getCurrency() );
			}
		}
	}

	public function test_fails_if_no_delivery_times_provided() {
		$this->expectException( InvalidValue::class );

		new DBShippingSettingsAdapter(
			[
				'currency' => 'USD',
				'db_rates' => [
					[
						'country' => 'US',
						'rate'    => 10.0,
						'options' => [],
					],
				],
			]
		);
	}

	public function test_country_currency_map_overrides_per_service_currency() {
		$db_rates = [
			[
				'country' => 'US',
				'rate'    => 10,
				'options' => [],
			],
			[
				'country' => 'FR',
				'rate'    => 5,
				'options' => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'FR' => 'EUR',
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
				'db_rates'             => $db_rates,
			]
		);

		$services = $settings->getServices();

		$this->assertCount( 2, $services );
		foreach ( $services as $service ) {
			if ( 'US' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'USD', $service->getCurrency() );
				$this->assertEquals( 'USD', $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency() );
			} elseif ( 'FR' === $service->getDeliveryCountry() ) {
				$this->assertEquals( 'EUR', $service->getCurrency() );
				$this->assertEquals( 'EUR', $service->getRateGroups()[0]->getSingleValue()->getFlatRate()->getCurrency() );
			}
		}
	}
}
