<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\StatesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

/**
 * Class StatesRateGroupAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter
 */
class StatesRateGroupAdapterTest extends UnitTest {

	public function test_maps_location_rates() {
		$location_rates = [
			new LocationRate( new ShippingLocation( 1001, 'US', 'CA' ), new ShippingRate( 110 ) ),
			new LocationRate( new ShippingLocation( 1002, 'US', 'NV' ), new ShippingRate( 410 ) ),
		];

		$rate_group = new StatesRateGroupAdapter(
			[
				'location_rates' => $location_rates,
				'currency'       => 'USD',
			]
		);

		$table = $rate_group->to_array()['mainTable'];

		$this->assertCount( 2, $table['rowHeaders']['locations'] );
		$this->assertCount( 2, $table['rows'] );

		$id_sets = array_map(
			function ( array $location_id_set ) {
				return $location_id_set['locationIds'];
			},
			$table['rowHeaders']['locations']
		);

		$this->assertEqualSets(
			[
				[ '1001' ],
				[ '1002' ],
			],
			$id_sets
		);

		$rates = array_map(
			function ( array $row ) {
				$this->assertCount( 1, $row['cells'] );

				return $row['cells'][0]['flatRate']['amountMicros'];
			},
			$table['rows']
		);

		$this->assertEqualSets(
			[
				'110000000',
				'410000000',
			],
			$rates
		);
	}

	public function test_fails_if_no_rates_provided() {
		$this->expectException( InvalidValue::class );

		new StatesRateGroupAdapter(
			[
				'currency' => 'USD',
			]
		);
	}

	public function test_fails_if_no_currency_provided() {
		$this->expectException( InvalidValue::class );

		new StatesRateGroupAdapter(
			[
				'location_rates' => [ new LocationRate( new ShippingLocation( 1001, 'US', 'CA' ), new ShippingRate( 110 ) ) ],
			]
		);
	}
}
