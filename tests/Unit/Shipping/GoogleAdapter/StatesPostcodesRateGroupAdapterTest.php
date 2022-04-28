<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\StatesPostcodesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\PostcodeRange;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Google\Service\ShoppingContent\LocationIdSet;
use Google\Service\ShoppingContent\Row;

/**
 * Class StatesPostcodesRateGroupAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter
 */
class StatesPostcodesRateGroupAdapterTest extends UnitTest {
	public function test_maps_location_rates() {
		$postcode_ranges = [
			new PostcodeRange( '4000', '4001' ),
			new PostcodeRange( '5000', '5001' )
		];

		$location_rates = [
			new LocationRate( new ShippingLocation( 1001, 'US', 'CA', $postcode_ranges ), new ShippingRate( 110 ) ),
		];

		$rate_group = new StatesPostcodesRateGroupAdapter(
			[
				'location_rates' => $location_rates,
				'currency'       => 'USD',
			]
		);

		$table = $rate_group->getMainTable();

		$this->assertCount( 1, $table->getColumnHeaders()->getPostalCodeGroupNames() );
		$this->assertCount( 1, $table->getRowHeaders()->getLocations() );
		$this->assertCount( 1, $table->getRows() );

		$this->assertEquals(
			[
				'US - 4000...4001,5000...5001',
			],
			$table->getColumnHeaders()->getPostalCodeGroupNames()
		);

		$id_sets = array_map(
			function ( LocationIdSet $location_id_set ) {
				return $location_id_set->getLocationIds();
			},
			$table->getRowHeaders()->getLocations()
		);
		$this->assertEqualSets(
			[
				[ 1001 ],
			],
			$id_sets
		);

		$rates = array_map(
			function ( Row $row ) {
				$this->assertCount( 1, $row->getCells() );

				return $row->getCells()[0]->getFlatRate()->getValue();
			},
			$table->getRows()
		);

		$this->assertEquals(
			[ 110 ],
			$rates
		);
	}

	public function test_maps_location_rates_fails_if_rates_not_provided_for_all_postcodes_states_combinations() {
		$location_rates = [
			new LocationRate( new ShippingLocation( 1001, 'US', 'CA', [ new PostcodeRange( '4000', '4001' ) ] ), new ShippingRate( 110 ) ),
			new LocationRate( new ShippingLocation( 1002, 'US', 'NV', [ new PostcodeRange( '3000', '3001' ) ] ), new ShippingRate( 410 ) ),
		];

		$this->expectException( InvalidValue::class );

		new StatesPostcodesRateGroupAdapter(
			[
				'location_rates' => $location_rates,
				'currency'       => 'USD',
			]
		);
	}

	public function test_fails_if_no_rates_provided() {
		$this->expectException( InvalidValue::class );

		new StatesPostcodesRateGroupAdapter(
			[
				'currency' => 'USD',
			]
		);
	}

	public function test_fails_if_no_currency_provided() {
		$this->expectException( InvalidValue::class );

		new StatesPostcodesRateGroupAdapter(
			[
				'location_rates' => new LocationRate( new ShippingLocation( 0, 'US', 'CA', [ new PostcodeRange( '4000', '4001' ) ] ), new ShippingRate( 110 ) ),
			]
		);
	}
}
