<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
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

		$services = $settings->get_services();

		$this->assertCount( 2, $services );
		$this->assertCount( 1, $services[0]['rateGroups'] );
		$this->assertCount( 1, $services[1]['rateGroups'] );

		foreach ( $services as $service ) {
			$country   = $service['deliveryCountries'][0];
			$flat_rate = $service['rateGroups'][0]['singleValue']['flatRate'];

			// Assert that the delivery country of both services is either US or AU.
			$this->assertTrue( in_array( $country, [ 'US', 'AU' ], true ) );
			$this->assertSame( 'DELIVERY', $service['shipmentType'] );

			if ( 'US' === $country ) {
				$this->assertEquals( 'USD', $flat_rate['currencyCode'] );
				$this->assertEquals( '10000000', $flat_rate['amountMicros'] );
				$this->assertEquals( 1, $service['deliveryTime']['minTransitDays'] );
				$this->assertEquals( 3, $service['deliveryTime']['maxTransitDays'] );
			} elseif ( 'AU' === $country ) {
				$this->assertEquals( 'USD', $flat_rate['currencyCode'] );
				$this->assertEquals( '50000000', $flat_rate['amountMicros'] );
				$this->assertEquals( 2, $service['deliveryTime']['minTransitDays'] );
				$this->assertEquals( 4, $service['deliveryTime']['maxTransitDays'] );
			}
		}
	}

	public function test_skips_country_without_delivery_time_and_reports_error() {
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

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
				],
				'db_rates'       => $db_rates,
			]
		);

		$services = $settings->get_services();

		// The country without a shipping time is left out with an error naming
		// it; the remaining country's service still syncs.
		$this->assertCount( 1, $services );
		$this->assertEquals( 'US', $services[0]['deliveryCountries'][0] );
		$this->assertCount( 1, $reported );
		$this->assertStringContainsString( 'AU', $reported[0] );
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

		$this->assertEmpty( $settings->get_services() );
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

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( '100000000', $services[0]['minimumOrderValue']['amountMicros'] );
		$this->assertCount( 1, $services[0]['rateGroups'] );
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

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertCount( 1, $services[0]['rateGroups'] );
		$this->assertEquals( '0', $services[0]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
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

		$services = $settings->get_services();

		$this->assertCount( 2, $services );
		$this->assertCount( 1, $services[0]['rateGroups'] );
		$this->assertCount( 1, $services[1]['rateGroups'] );

		foreach ( $services as $service ) {
			if ( '0' === $service['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] ) {
				$this->assertEquals( '100000000', $service['minimumOrderValue']['amountMicros'] );
			} else {
				$this->assertArrayNotHasKey( 'minimumOrderValue', $service );
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

		$services = $settings->get_services();

		$this->assertCount( 2, $services );

		foreach ( $services as $service ) {
			$rate_currency = $service['rateGroups'][0]['singleValue']['flatRate']['currencyCode'];

			if ( 'US' === $service['deliveryCountries'][0] ) {
				$this->assertEquals( 'USD', $service['currencyCode'] );
				$this->assertEquals( 'USD', $rate_currency );
			} elseif ( 'FR' === $service['deliveryCountries'][0] ) {
				$this->assertEquals( 'EUR', $service['currencyCode'] );
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

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( 'GBP', $services[0]['currencyCode'] );
		$this->assertEquals(
			'GBP',
			$services[0]['rateGroups'][0]['singleValue']['flatRate']['currencyCode']
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

		$services = $settings->get_services();

		$this->assertCount( 1, $services );
		$this->assertEquals( '75000000', $services[0]['minimumOrderValue']['amountMicros'] );
		$this->assertEquals( 'EUR', $services[0]['minimumOrderValue']['currencyCode'] );
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

		$services = $settings->get_services();

		$this->assertCount( 2, $services );

		foreach ( $services as $service ) {
			$rate_value = (float) $service['rateGroups'][0]['singleValue']['flatRate']['amountMicros'];
			$this->assertEquals( 'EUR', $service['currencyCode'] );

			if ( 0.0 === $rate_value ) {
				$this->assertEquals( '100000000', $service['minimumOrderValue']['amountMicros'] );
				$this->assertEquals( 'EUR', $service['minimumOrderValue']['currencyCode'] );
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

	public function test_fixed_exchange_rate_produces_a_flat_rate_service_without_wpml() {
		$db_rates = [
			[
				'country' => 'FR',
				'rate'    => 5,
				// A threshold that fails to convert drops the whole service, so it must use the
				// fixed rate too.
				'options' => [ 'free_shipping_threshold' => 200 ],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'               => 'USD',
				'country_currency_map'   => [
					'FR' => 'EUR',
				],
				'country_exchange_rates' => [
					'FR' => 0.9,
				],
				'delivery_times'         => [
					'FR' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'               => $db_rates,
			]
		);

		$services = $settings->get_services();

		// The conditional free service the threshold produces, plus the paid one.
		$this->assertCount( 2, $services );
		foreach ( $services as $service ) {
			$this->assertEquals( 'EUR', $service['currencyCode'] );
		}
		// The 200 USD threshold is converted at the fixed rate, not passed through unconverted.
		$this->assertSame( '180000000', $services[0]['minimumOrderValue']['amountMicros'] );
		// 5 USD at the same rate.
		$this->assertSame( '4500000', $services[1]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
	}

	public function test_without_a_fixed_rate_or_wpml_the_service_is_still_left_out_with_an_error() {
		// Unchanged behaviour: no rate and no conversion means no service, reported by country
		// and currency so the merchant can act on it.
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'FR' => 'EUR',
				],
				'delivery_times'       => [
					'FR' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'             => [
					[
						'country' => 'FR',
						'rate'    => 5,
						'options' => [],
					],
				],
			]
		);

		$this->assertCount( 0, $settings->get_services() );
		$this->assertNotEmpty( $reported );
		$this->assertStringContainsString( 'EUR', $reported[0] );
		$this->assertStringContainsString( 'FR', $reported[0] );
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
				'wpml'                 => $this->create_wpml_doubling_converter(),
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

		$services = $settings->get_services();

		$this->assertCount( 2, $services );
		foreach ( $services as $service ) {
			if ( 'US' === $service['deliveryCountries'][0] ) {
				$this->assertEquals( 'USD', $service['currencyCode'] );
				$this->assertEquals( 'USD', $service['rateGroups'][0]['singleValue']['flatRate']['currencyCode'] );
			} elseif ( 'FR' === $service['deliveryCountries'][0] ) {
				$this->assertEquals( 'EUR', $service['currencyCode'] );
				$this->assertEquals( 'EUR', $service['rateGroups'][0]['singleValue']['flatRate']['currencyCode'] );

				// The store-currency amount is converted, not relabelled:
				// 5 USD doubles to 10 EUR under the test converter.
				$this->assertSame( '10000000', $service['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
			}
		}
	}

	public function test_creates_one_service_per_currency_for_a_multi_currency_country() {
		$db_rates = [
			[
				'country' => 'AE',
				'rate'    => 50,
				'options' => [
					'free_shipping_threshold' => 200,
				],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'AE' => [ 'USD', 'AED' ],
				],
				'wpml'                 => $this->create_wpml_doubling_converter(),
				'delivery_times'       => [
					'AE' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'             => $db_rates,
			]
		);

		$services = $settings->get_services();

		// One paid service plus one conditional free-shipping service per currency.
		$this->assertCount( 4, $services );

		$paid_by_currency = [];
		foreach ( $services as $service ) {
			$this->assertEquals( 'AE', $service['deliveryCountries'][0] );

			if ( 0.0 !== (float) $service['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] ) {
				$paid_by_currency[ $service['currencyCode'] ] = $service;
			}
		}

		// The store-currency service keeps the row amount; the AED service
		// carries the converted amount (50 doubled to 100).
		$this->assertSame( '50000000', $paid_by_currency['USD']['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
		$this->assertSame( '100000000', $paid_by_currency['AED']['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
	}

	public function test_row_in_non_store_currency_only_serves_its_own_currency() {
		$reported = [];
		add_action(
			'woocommerce_gla_error',
			function ( $message ) use ( &$reported ) {
				$reported[] = $message;
			}
		);

		$db_rates = [
			[
				'country'  => 'FR',
				'currency' => 'EUR',
				'rate'     => 8,
				'options'  => [],
			],
		];

		$settings = new DBShippingSettingsAdapter(
			[
				'currency'             => 'USD',
				'country_currency_map' => [
					'FR' => [ 'EUR', 'USD' ],
				],
				'wpml'                 => $this->create_wpml_doubling_converter(),
				'delivery_times'       => [
					'FR' => [
						'time'     => 1,
						'max_time' => 1,
					],
				],
				'db_rates'             => $db_rates,
			]
		);

		$services = $settings->get_services();

		// There is no conversion path between two non-store currencies, so
		// only the row's own currency gets a service and the other mapped
		// currency is reported.
		$this->assertCount( 1, $services );
		$this->assertEquals( 'EUR', $services[0]['currencyCode'] );
		$this->assertSame( '8000000', $services[0]['rateGroups'][0]['singleValue']['flatRate']['amountMicros'] );
		$this->assertCount( 1, $reported );
		$this->assertStringContainsString( 'USD', $reported[0] );
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
