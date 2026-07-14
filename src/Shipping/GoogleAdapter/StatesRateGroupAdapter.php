<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;

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
	 * Map the location rates onto a table keyed by geotarget location ids.
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
			$location_id_sets[ $location_id ] = [ 'locationIds' => [ (string) $location_id ] ];

			$rows[ $location_id ] = [ 'cells' => [ $this->create_value( (float) $location_rate->get_shipping_rate()->get_rate(), $currency ) ] ];
		}

		$this->rate_group['mainTable'] = [
			'rowHeaders' => [ 'locations' => array_values( $location_id_sets ) ],
			'rows'       => array_values( $rows ),
		];
	}
}
