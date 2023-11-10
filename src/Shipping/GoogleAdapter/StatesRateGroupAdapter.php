<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Headers;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\LocationIdSet;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Row;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class StatesRateGroupAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class StatesRateGroupAdapter extends AbstractRateGroupAdapter {
	/**
	 * Map the location rates to the class properties.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency
	 *
	 * @return void
	 */
	protected function map_location_rates( array $location_rates, string $currency ): void {
		$location_id_sets = [];
		$rows             = [];
		foreach ( $location_rates as $location_rate ) {
			$location_id                      = $location_rate->get_location()->get_google_id();
			$location_id_sets[ $location_id ] = new LocationIdSet( [ 'locationIds' => [ $location_id ] ] );

			$rows[ $location_id ] = new Row( [ 'cells' => [ $this->create_value_object( $location_rate->get_shipping_rate()->get_rate(), $currency ) ] ] );
		}

		$table = new Table(
			[
				'rowHeaders' => new Headers( [ 'locations' => array_values( $location_id_sets ) ] ),
				'rows'       => array_values( $rows ),
			]
		);

		$this->setMainTable( $table );
	}
}
