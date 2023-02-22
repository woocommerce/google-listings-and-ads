<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\StatesRateGroupAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\LocationIdSet;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Row;

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

		$table = $rate_group->getMainTable();

		$this->assertCount( 2, $table->getRowHeaders()->getLocations() );
		$this->assertCount( 2, $table->getRows() );

		$id_sets = array_map(
			function ( LocationIdSet $location_id_set ) {
				return $location_id_set->getLocationIds();
			},
			$table->getRowHeaders()->getLocations()
		);

		$this->assertEqualSets(
			[
				[ 1001 ],
				[ 1002 ],
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

		$this->assertEqualSets(
			[
				110,
				410,
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
