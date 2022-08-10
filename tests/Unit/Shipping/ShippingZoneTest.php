<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Shipping_Flat_Rate;
use WC_Shipping_Free_Shipping;
use WC_Shipping_Method;
use WC_Shipping_Zone;

/**
 * Class ShippingZoneTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping
 *
 * @property MockObject|WC           $wc
 * @property MockObject|GoogleHelper $google_helper
 * @property ShippingZone            $shipping_zone
 */
class ShippingZoneTest extends UnitTest {

	public function test_returns_supported_shipping_methods() {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);

		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		// Adding one unsupported shipping method. This method should not be returned.
		$unsupported_method     = $this->createMock( WC_Shipping_Method::class );
		$unsupported_method->id = 'unsupported_method';

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate, $free_shipping, $unsupported_method ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertCount( 2, $methods );

		$methods_ids = array_map(
			function ( $method ) {
				return $method['id'];
			},
			$methods
		);

		$this->assertEqualSets(
			[
				ShippingZone::METHOD_FLAT_RATE,
				ShippingZone::METHOD_FREE,
			],
			$methods_ids
		);
	}

	public function test_ignores_flat_rate_methods_with_null_cost() {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return null;
					}
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertEmpty( $methods );
	}

	public function test_returns_shipping_method_properties() {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		// Create a sample flat-rate shipping method with a constant cost.
		$flat_rate        = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id    = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->title = 'Flat Rate';
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertCount( 1, $methods );
		$this->assertEquals( ShippingZone::METHOD_FLAT_RATE, $methods[0]['id'] );
		$this->assertEquals( 'Flat Rate', $methods[0]['title'] );
		$this->assertEquals( 'USD', $methods[0]['currency'] );
		$this->assertNotEmpty( $methods[0]['options'] );
		$this->assertEquals( 10, $methods[0]['options']['cost'] );
	}

	/**
	 * @param string $requires
	 *
	 * @dataProvider return_free_shipping_min_amount_requirements
	 */
	public function test_returns_free_shipping_method_requires_min_amount( string $requires ) {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) use ( $requires ) {
					if ( 'requires' === $option ) {
						return $requires;
					}
					if ( 'min_amount' === $option ) {
						return 99.99;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertCount( 1, $methods );
		$this->assertEquals( ShippingZone::METHOD_FREE, $methods[0]['id'] );
		$this->assertNotEmpty( $methods[0]['options'] );
		$this->assertEquals( 99.99, $methods[0]['options']['min_amount'] );
	}

	/**
	 * @param string $requires
	 *
	 * @dataProvider return_free_shipping_coupon_requirements
	 */
	public function test_ignores_free_shipping_method_requires_coupon( string $requires ) {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) use ( $requires ) {
					if ( 'requires' === $option ) {
						return $requires;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertCount( 0, $methods );
	}

	public function test_ignores_methods_with_mathematical_cost() {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		// Create a sample flat-rate shipping method with a constant cost.
		$flat_rate        = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id    = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->title = 'Flat Rate';
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return '[qty] * 5';
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertEmpty( $methods );
	}

	public function test_returns_shipping_countries() {
		$shipping_zones = [
			[
				'id'             => 0,
				'zone_id'        => 0,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object) [
						'code' => 'US:NV',
						'type' => 'state',
					],
					(object) [
						'code' => 'US:CA',
						'type' => 'state',
					],
				],
			],
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'EU branches',
				'zone_locations' => [
					(object) [
						'code' => 'GB',
						'type' => 'country',
					],
					(object) [
						'code' => 'FR',
						'type' => 'country',
					],
				],
			],
			[
				'id'             => 2,
				'zone_id'        => 2,
				'zone_name'      => 'EU (Other)',
				'zone_locations' => [
					(object) [
						'code' => 'EU',
						'type' => 'continent',
					],
				],
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations and methods for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturnCallback(
				function ( $zone_id ) use ( $shipping_zones ) {
					$zone = $this->createMock( WC_Shipping_Zone::class );
					$zone->expects( $this->any() )
						->method( 'get_zone_locations' )
						->willReturn( $shipping_zones[ $zone_id ]['zone_locations'] );
					// We need at least one method for each country in order for it to show up in the list.
					$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
					$free_shipping->id = ShippingZone::METHOD_FREE;
					$free_shipping->expects( $this->any() )
						->method( 'is_enabled' )
						->willReturn( true );
					$zone->expects( $this->any() )
						->method( 'get_shipping_methods' )
						->willReturn( [ $free_shipping ] );

					return $zone;
				}
			);

		// Mock the GoogleHelper class to return the list of supported countries for the EU continent.
		$this->google_helper->expects( $this->any() )
			->method( 'get_supported_countries_from_continent' )
			->willReturn(
				[
					'GB',
					'FR',
					'DE',
					'DK',
				]
			);

		$this->assertEqualSets(
			[
				'US',
				'GB',
				'FR',
				'DE',
				'DK',
			],
			$this->shipping_zone->get_shipping_countries()
		);
	}

	public function test_ignores_shipping_countries_with_no_methods() {
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$shipping_zones = [
			[
				'id'             => 0,
				'zone_id'        => 0,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object) [
						'code' => 'GB',
						'type' => 'country',
					],
				],
				'methods'        => [
					$free_shipping,
				],
			],
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'France',
				'zone_locations' => [
					(object) [
						'code' => 'FR',
						'type' => 'country',
					],
				],
				'methods'        => [], // No methods for France.
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations and methods for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturnCallback(
				function ( $zone_id ) use ( $shipping_zones ) {
					$zone = $this->createMock( WC_Shipping_Zone::class );
					$zone->expects( $this->any() )
						->method( 'get_zone_locations' )
						->willReturn( $shipping_zones[ $zone_id ]['zone_locations'] );
					$zone->expects( $this->any() )
						->method( 'get_shipping_methods' )
						->willReturn( $shipping_zones[ $zone_id ]['methods'] );

					return $zone;
				}
			);

		$this->assertEquals( [ 'GB' ], $this->shipping_zone->get_shipping_countries() );
	}

	public function test_returns_shipping_method_with_higher_cost() {
		// Create a sample flat-rate shipping method with a constant cost.
		$flat_rate_1     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate_1->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate_1->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate_1->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);
		// Create another flat-rate shipping method with a higher cost.
		$flat_rate_2     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate_2->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate_2->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate_2->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 20;
					}

					return null;
				}
			);
		$shipping_zones = [
			[
				'id'             => 0,
				'zone_id'        => 0,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object) [
						'code' => 'US:NV',
						'type' => 'state',
					],
				],
				'methods'        => [
					$flat_rate_1,
				],
			],
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'CA',
				'zone_locations' => [
					(object) [
						'code' => 'US:CA',
						'type' => 'state',
					],
				],
				'methods'        => [
					$flat_rate_2,
				],
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations and methods for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturnCallback(
				function ( $zone_id ) use ( $shipping_zones ) {
					$zone = $this->createMock( WC_Shipping_Zone::class );
					$zone->expects( $this->any() )
						->method( 'get_zone_locations' )
						->willReturn( $shipping_zones[ $zone_id ]['zone_locations'] );
					$zone->expects( $this->any() )
						->method( 'get_shipping_methods' )
						->willReturn( $shipping_zones[ $zone_id ]['methods'] );

					return $zone;
				}
			);

		$this->assertEquals( [ 'US' ], $this->shipping_zone->get_shipping_countries() );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );
		$this->assertCount( 1, $methods );
		$this->assertEquals( ShippingZone::METHOD_FLAT_RATE, $methods[0]['id'] );
		$this->assertEquals( 20, $methods[0]['options']['cost'] );
	}

	public function test_returns_shipping_method_with_higher_min_order_amount() {
		// Create a sample free-shipping method with a min amount option.
		$free_shipping_1     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping_1->id = ShippingZone::METHOD_FREE;
		$free_shipping_1->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping_1->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'requires' === $option ) {
						return 'min_amount';
					}
					if ( 'min_amount' === $option ) {
						return 10.99;
					}

					return null;
				}
			);

		// Create another free-shipping method with a higher min amount option.
		$free_shipping_2     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping_2->id = ShippingZone::METHOD_FREE;
		$free_shipping_2->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping_2->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'requires' === $option ) {
						return 'min_amount';
					}
					if ( 'min_amount' === $option ) {
						return 50.99;
					}

					return null;
				}
			);
		$shipping_zones = [
			[
				'id'             => 0,
				'zone_id'        => 0,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object) [
						'code' => 'US:NV',
						'type' => 'state',
					],
				],
				'methods'        => [
					$free_shipping_1,
				],
			],
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'CA',
				'zone_locations' => [
					(object) [
						'code' => 'US:CA',
						'type' => 'state',
					],
				],
				'methods'        => [
					$free_shipping_2,
				],
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations and methods for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturnCallback(
				function ( $zone_id ) use ( $shipping_zones ) {
					$zone = $this->createMock( WC_Shipping_Zone::class );
					$zone->expects( $this->any() )
						->method( 'get_zone_locations' )
						->willReturn( $shipping_zones[ $zone_id ]['zone_locations'] );
					$zone->expects( $this->any() )
						->method( 'get_shipping_methods' )
						->willReturn( $shipping_zones[ $zone_id ]['methods'] );

					return $zone;
				}
			);

		$this->assertEquals( [ 'US' ], $this->shipping_zone->get_shipping_countries() );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );
		$this->assertCount( 1, $methods );
		$this->assertEquals( ShippingZone::METHOD_FREE, $methods[0]['id'] );
		$this->assertEquals( 50.99, $methods[0]['options']['min_amount'] );
	}

	public function test_returns_shipping_method_with_existing_min_order_amount() {
		// Create a sample free-shipping method WITHOUT min amount option.
		$free_shipping_1     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping_1->id = ShippingZone::METHOD_FREE;
		$free_shipping_1->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		// Create another free-shipping method with a min amount option specified.
		$free_shipping_2     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping_2->id = ShippingZone::METHOD_FREE;
		$free_shipping_2->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping_2->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'requires' === $option ) {
						return 'min_amount';
					}
					if ( 'min_amount' === $option ) {
						return 10;
					}

					return null;
				}
			);
		$shipping_zones = [
			[
				'id'             => 0,
				'zone_id'        => 0,
				'zone_name'      => 'Local',
				'zone_locations' => [
					(object) [
						'code' => 'US:NV',
						'type' => 'state',
					],
				],
				'methods'        => [
					$free_shipping_1,
				],
			],
			[
				'id'             => 1,
				'zone_id'        => 1,
				'zone_name'      => 'CA',
				'zone_locations' => [
					(object) [
						'code' => 'US:CA',
						'type' => 'state',
					],
				],
				'methods'        => [
					$free_shipping_2,
				],
			],
		];

		// Mock the get_shipping_zones method to return the above array.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( $shipping_zones );

		// Mock the get_shipping_zone method to return the zone locations and methods for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturnCallback(
				function ( $zone_id ) use ( $shipping_zones ) {
					$zone = $this->createMock( WC_Shipping_Zone::class );
					$zone->expects( $this->any() )
						->method( 'get_zone_locations' )
						->willReturn( $shipping_zones[ $zone_id ]['zone_locations'] );
					$zone->expects( $this->any() )
						->method( 'get_shipping_methods' )
						->willReturn( $shipping_zones[ $zone_id ]['methods'] );

					return $zone;
				}
			);

		$this->assertEquals( [ 'US' ], $this->shipping_zone->get_shipping_countries() );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );
		$this->assertCount( 1, $methods );
		$this->assertEquals( ShippingZone::METHOD_FREE, $methods[0]['id'] );
		$this->assertEquals( 10, $methods[0]['options']['min_amount'] );
	}

	public function test_is_shipping_method_supported() {
		$this->assertTrue( ShippingZone::is_shipping_method_supported( ShippingZone::METHOD_FLAT_RATE ) );
		$this->assertFalse( ShippingZone::is_shipping_method_supported( 'some_random_method_that_should_not_be_valid' ) );
	}

	public function test_returns_shipping_class_costs() {
		// Return one sample shipping zone.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		// Return three sample shipping classes.
		$light_class          = new \stdClass();
		$light_class->term_id = 0;
		$light_class->slug    = 'light';
		$heavy_class          = new \stdClass();
		$heavy_class->term_id = 1;
		$heavy_class->slug    = 'heavy';
		$qty_class            = new \stdClass();
		$qty_class->term_id   = 2;
		$qty_class->slug      = 'qty';
		$shipping_classes     = [ $light_class, $heavy_class, $qty_class ];
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_classes' )
			->willReturn( $shipping_classes );

		// Create a sample flat-rate shipping method with a constant cost.
		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					} elseif ( 'class_cost_0' === $option ) {
						return 5;
					} elseif ( 'class_cost_1' === $option ) {
						return 15;
					} elseif ( 'class_cost_2' === $option ) {
						// This one has a dynamic price. It should be ignored.
						return '[qty] / 10';
					} elseif ( 'no_class_cost' === $option ) {
						return 2;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );
		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$methods = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertCount( 1, $methods );

		// The shipping class with a dynamic price should be ignored.
		$this->assertCount( 2, $methods[0]['options']['class_costs'] );

		// The `no_class_cost` should be added to the flat rate method cost (10+2=12).
		$this->assertEquals( 12, $methods[0]['options']['cost'] );

		// The shipping class costs should be added to the flat rate method cost (10+5=15 and 10+15=25).
		$this->assertEquals( 15, $methods[0]['options']['class_costs']['light'] );
		$this->assertEquals( 25, $methods[0]['options']['class_costs']['heavy'] );

	}

	public function test_doesnt_return_disabled_methods() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( false );

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_methods_for_country( 'US' );

		$this->assertEmpty( $rates );
	}

	public function test_doesnt_return_unsupported_rates() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate, $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( ShippingZone::METHOD_FLAT_RATE, $rates[0]['method'] );
	}

	public function test_doesnt_return_disabled_rates() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( false );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertEmpty( $rates );
	}

	public function test_returns_shipping_rate_in_correct_format() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);

		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$free_shipping->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'requires' === $option ) {
						return 'min_amount';
					}
					if ( 'min_amount' === $option ) {
						return 100;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate, $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( 'US', $rates[0]['country'] );
		$this->assertEquals( 'flat_rate', $rates[0]['method'] );
		$this->assertEquals( 'USD', $rates[0]['currency'] );
		$this->assertEquals( 10, $rates[0]['rate'] );
		$this->assertEquals( 100, $rates[0]['options']['free_shipping_threshold'] );
	}

	public function test_returns_shipping_rate_with_zero_cost_if_free_shipping_exists() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate, $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( 0, $rates[0]['rate'] );
	}

	public function test_returns_shipping_rate_with_zero_cost_if_only_free_shipping_enabled() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( false );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					}

					return null;
				}
			);
		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate, $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( 0, $rates[0]['rate'] );
	}

	public function test_returns_shipping_rate_with_zero_cost_if_only_free_shipping_exists() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		$free_shipping     = $this->createMock( WC_Shipping_Free_Shipping::class );
		$free_shipping->id = ShippingZone::METHOD_FREE;
		$free_shipping->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $free_shipping ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( 0, $rates[0]['rate'] );
	}

	public function test_returns_class_shipping_rates() {
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zones' )
			->willReturn( [ [ 'zone_id' => 1 ] ] );

		// Return a sample shipping class.
		$light_class          = new \stdClass();
		$light_class->term_id = 0;
		$light_class->slug    = 'light';
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_classes' )
			->willReturn( [ $light_class ] );

		// Create a sample flat-rate shipping method with a constant cost.
		$flat_rate     = $this->createMock( WC_Shipping_Flat_Rate::class );
		$flat_rate->id = ShippingZone::METHOD_FLAT_RATE;
		$flat_rate->expects( $this->any() )
			->method( 'is_enabled' )
			->willReturn( true );
		$flat_rate->expects( $this->any() )
			->method( 'get_option' )
			->willReturnCallback(
				function ( $option ) {
					if ( 'cost' === $option ) {
						return 10;
					} elseif ( 'class_cost_0' === $option ) {
						return 5;
					} elseif ( 'no_class_cost' === $option ) {
						return 2;
					}

					return null;
				}
			);

		$shipping_zone = $this->create_mock_shipping_zone( 'US', [ $flat_rate ] );

		// Return the zone locations for the given zone id.
		$this->wc->expects( $this->any() )
			->method( 'get_shipping_zone' )
			->willReturn( $shipping_zone );

		$rates = $this->shipping_zone->get_shipping_rates_for_country( 'US' );

		$this->assertCount( 1, $rates );
		$this->assertEquals( 12, $rates[0]['rate'] );
		$this->assertNotEmpty( $rates[0]['options']['shipping_class_rates'] );
		$this->assertEquals( 'light', $rates[0]['options']['shipping_class_rates'][0]['class'] );
		$this->assertEquals( 15, $rates[0]['options']['shipping_class_rates'][0]['rate'] );
	}

	/**
	 * Creates a mock WooCommerce shipping zone object covering the given country and including the given shipping methods.
	 *
	 * @param string $country
	 * @param array  $methods
	 *
	 * @return WC_Shipping_Zone|MockObject
	 */
	protected function create_mock_shipping_zone( string $country, array $methods ) {
		$shipping_zone = $this->createMock( WC_Shipping_Zone::class );
		$shipping_zone->expects( $this->any() )
			->method( 'get_zone_locations' )
			->willReturn(
				[
					(object) [
						'code' => $country,
						'type' => 'country',
					],
				]
			);
		$shipping_zone->expects( $this->any() )
			->method( 'get_shipping_methods' )
			->willReturn( $methods );

		return $shipping_zone;
	}

	/**
	 * Returns two options for the `requires` options of a free-shipping method that require a minimum order amount.
	 *
	 * @return array
	 */
	public function return_free_shipping_min_amount_requirements(): array {
		return [
			[ 'min_amount' ],
			[ 'either' ],
		];
	}

	/**
	 * Returns two options for the `requires` options of a free-shipping method that require a coupon.
	 *
	 * @return array
	 */
	public function return_free_shipping_coupon_requirements(): array {
		return [
			[ 'coupon' ],
			[ 'both' ],
		];
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->wc = $this->createMock( WC::class );
		$this->wc->expects( $this->any() )
			->method( 'get_woocommerce_currency' )
			->willReturn( 'USD' );

		$this->google_helper = $this->createMock( GoogleHelper::class );
		// Mock Merchant Center supported countries.
		$this->google_helper->expects( $this->any() )
			->method( 'get_mc_supported_countries' )
			->willReturn(
				[
					'US',
					'GB',
					'FR',
					'DE',
					'DK',
				]
			);

		$this->shipping_zone = new ShippingZone( $this->wc, $this->google_helper );
	}
}
