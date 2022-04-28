<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Google\Service\ShoppingContent\Headers;
use Google\Service\ShoppingContent\LocationIdSet;
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
class StatesPostcodesRateGroupAdapter extends AbstractRateGroupAdapter {
	/**
	 * Map the location rates to the class properties.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency
	 *
	 * @return void
	 *
	 * @throws InvalidValue If shipping rates are not provided for all postal codes + states combinations.
	 */
	protected function map_location_rates( array $location_rates, string $currency ): void {
		$location_id_sets = [];
		$postal_codes     = [];
		$row_values       = [];
		foreach ( $location_rates as $location_rate ) {
			$location_id                      = $location_rate->get_location()->get_google_id();
			$location_id_sets[ $location_id ] = new LocationIdSet( [ 'locationIds' => [ $location_id ] ] );
			$postcode_name                    = $location_rate->get_location()->get_postcode_group_name();
			$postal_codes[ $postcode_name ]   = $postcode_name;

			$row_values[ $location_id ]                   = $row_values[ $location_id ] ?? [];
			$row_values[ $location_id ][ $postcode_name ] = $this->create_value_object( $location_rate->get_shipping_rate()->get_rate(), $currency );
		}

		foreach ( $row_values as $postcode_values ) {
			if ( count( $postcode_values ) !== count( $postal_codes ) ) {
				throw new InvalidValue( 'Shipping rates must be provided for ALL postal codes & location combinations.' );
			}
		}

		$rows = array_map(
			function ( $postcodes_rates ) {
				return new Row( [ 'cells' => array_values( $postcodes_rates ) ] );
			},
			$row_values
		);

		$table = new Table(
			[
				'rowHeaders'    => new Headers( [ 'locations' => array_values( $location_id_sets ) ] ),
				'columnHeaders' => new Headers( [ 'postalCodeGroupNames' => array_values( $postal_codes ) ] ),
				'rows'          => array_values( $rows ),
			]
		);

		$this->setMainTable( $table );
	}

}
