<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostcodesRateGroupAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class PostcodesRateGroupAdapter extends AbstractRateGroupAdapter {
	/**
	 * Map the location rates onto a table keyed by postcode region ids.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency
	 *
	 * @return void
	 */
	protected function map_location_rates( array $location_rates, string $currency ): void {
		$postal_codes = [];
		$rows         = [];
		foreach ( $location_rates as $location_rate ) {
			$region = $location_rate->get_location()->get_shipping_region();
			if ( empty( $region ) ) {
				continue;
			}

			$postcode_name                  = $region->get_id();
			$postal_codes[ $postcode_name ] = $postcode_name;

			$rows[ $postcode_name ] = [ 'cells' => [ $this->create_value( (float) $location_rate->get_shipping_rate()->get_rate(), $currency ) ] ];
		}

		$this->rate_group['mainTable'] = [
			'rowHeaders' => [ 'postalCodeGroupNames' => array_values( $postal_codes ) ],
			'rows'       => array_values( $rows ),
		];
	}
}
