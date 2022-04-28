<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Google\Service\ShoppingContent\Headers;
use Google\Service\ShoppingContent\Row;
use Google\Service\ShoppingContent\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class CountryPostcodesRateGroupAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   x.x.x
 */
class CountryPostcodesRateGroupAdapter extends AbstractRateGroupAdapter {
	/**
	 * Map the location rates to the class properties.
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
			$postcode_name                  = $location_rate->get_location()->get_postcode_group_name();
			$postal_codes[ $postcode_name ] = $postcode_name;

			$rows[ $postcode_name ] = new Row( [ 'cells' => [ $this->create_value_object( $location_rate->get_shipping_rate()->get_rate(), $currency ) ] ] );
		}

		$table = new Table(
			[
				'rowHeaders' => new Headers( [ 'postalCodeGroupNames' => array_values( $postal_codes ) ] ),
				'rows'       => array_values( $rows ),
			]
		);

		$this->setMainTable( $table );
	}
}
