<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ServiceRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCShippingSettingsAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class WCShippingSettingsAdapter extends AbstractShippingSettingsAdapter {
	/**
	 * Parses the already validated input data and maps the provided shipping rates into services.
	 *
	 * @param array $data Validated data.
	 */
	protected function map_gla_data( array $data ): void {
		$this->map_rates_collections( $data['rates_collections'] );
	}

	/**
	 * Validates the input array provided to this class.
	 *
	 * @param array $data
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	protected function validate_gla_data( array $data ): void {
		parent::validate_gla_data( $data );

		if ( empty( $data['rates_collections'] ) || ! is_array( $data['rates_collections'] ) ) {
			throw new InvalidValue( 'The value of "rates_collections" must be a non empty array of CountryRatesCollection objects.' );
		} else {
			$this->validate_rates_collections( $data['rates_collections'] );
		}
	}

	/**
	 * Map the collections of location rates for each country to services and regions.
	 *
	 * @param CountryRatesCollection[] $rates_collections
	 *
	 * @return void
	 */
	protected function map_rates_collections( array $rates_collections ): void {
		foreach ( $rates_collections as $rates_collection ) {
			// array_replace, not array_merge: region ids are numeric strings and
			// array_merge would renumber them, breaking the table -> region reference.
			$this->regions = array_replace( $this->regions, $this->get_location_rates_regions( $rates_collection->get_location_rates() ) );

			foreach ( $rates_collection->get_rates_grouped_by_service() as $service_collection ) {
				$this->services[] = $this->create_shipping_service( $service_collection );
			}
		}
	}

	/**
	 * @param LocationRate[] $location_rates
	 * @param string         $shipping_area
	 * @param array          $applicable_classes
	 * @param string|null    $currency           Currency for the rate group prices. Must match the
	 *                                           currency of the shipping service the group belongs to.
	 *                                           Defaults to the store currency when omitted.
	 *
	 * @return array
	 *
	 * @throws InvalidArgument If an invalid value is provided for the shipping_area argument.
	 */
	protected function create_rate_group( array $location_rates, string $shipping_area, array $applicable_classes = [], ?string $currency = null ): array {
		$currency = $currency ?? $this->currency;

		switch ( $shipping_area ) {
			case ShippingLocation::COUNTRY_AREA:
				// Each country can only have one global rate.
				$country_rate = $location_rates[ array_key_first( $location_rates ) ];
				return $this->create_single_value_rate_group( $country_rate, $applicable_classes, $currency );
			case ShippingLocation::POSTCODE_AREA:
				return ( new PostcodesRateGroupAdapter(
					[
						'location_rates'           => $location_rates,
						'currency'                 => $currency,
						'applicableShippingLabels' => $applicable_classes,
					]
				) )->to_array();
			case ShippingLocation::STATE_AREA:
				return ( new StatesRateGroupAdapter(
					[
						'location_rates'           => $location_rates,
						'currency'                 => $currency,
						'applicableShippingLabels' => $applicable_classes,
					]
				) )->to_array();
			default:
				throw new InvalidArgument( 'Invalid shipping area.' );
		}
	}

	/**
	 * Create a shipping service.
	 *
	 * @param ServiceRatesCollection $service_collection
	 *
	 * @return array
	 */
	protected function create_shipping_service( ServiceRatesCollection $service_collection ): array {
		$country  = $service_collection->get_country();
		$currency = $this->get_currency_for_country( $country );

		// Rate group prices must be in the same currency as the service they
		// belong to, so the per-country currency is resolved before building them.
		$rate_groups   = [];
		$shipping_area = $service_collection->get_shipping_area();
		foreach ( $service_collection->get_rates_grouped_by_shipping_class() as $class => $location_rates ) {
			$applicable_classes    = ! empty( $class ) ? [ $class ] : [];
			$rate_groups[ $class ] = $this->create_rate_group( $location_rates, $shipping_area, $applicable_classes, $currency );
		}

		$service = [
			'serviceName'       => sprintf(
				/* translators: %1 is a random 4-digit string, %2 is the country code */
				__( '[%1$s] Google for WooCommerce generated service - %2$s', 'google-listings-and-ads' ),
				sprintf( '%04x', wp_rand( 0, 0xffff ) ),
				$country
			),
			'active'            => true,
			// One service per country; deliveryCountries is an array as MAPI requires.
			'deliveryCountries' => [ $country ],
			'currencyCode'      => $currency,
			'deliveryTime'      => $this->get_delivery_time( $country ),
			'shipmentType'      => 'DELIVERY',
			'rateGroups'        => array_values( $rate_groups ),
		];

		$min_order_amount = $service_collection->get_min_order_amount();
		if ( $min_order_amount ) {
			$service['minimumOrderValue'] = $this->mapi_price( (float) $min_order_amount, $currency );
		}

		return $service;
	}

	/**
	 * Extract and return the Merchant API regions for the given location rates, keyed by region id.
	 *
	 * @param LocationRate[] $location_rates
	 *
	 * @return array<string, array>
	 */
	protected function get_location_rates_regions( array $location_rates ): array {
		$regions = [];

		foreach ( $location_rates as $location_rate ) {
			$location = $location_rate->get_location();
			if ( empty( $location->get_shipping_region() ) ) {
				continue;
			}
			$region = $location->get_shipping_region();

			$postal_codes = [];
			foreach ( $region->get_postcode_ranges() as $postcode_range ) {
				$postal_code = [ 'begin' => (string) $postcode_range->get_start_code() ];
				$end         = (string) $postcode_range->get_end_code();
				if ( '' !== $end ) {
					$postal_code['end'] = $end;
				}
				$postal_codes[] = $postal_code;
			}

			$regions[ $region->get_id() ] = [
				'displayName'    => (string) $region->get_id(),
				'postalCodeArea' => [
					'regionCode'  => (string) $location->get_country(),
					'postalCodes' => $postal_codes,
				],
			];
		}

		return $regions;
	}

	/**
	 * @param LocationRate $location_rate
	 * @param string[]     $shipping_classes
	 * @param string|null  $currency         Currency for the rate group price. Must match the
	 *                                       currency of the shipping service the group belongs to.
	 *                                       Defaults to the store currency when omitted.
	 *
	 * @return array
	 */
	protected function create_single_value_rate_group( LocationRate $location_rate, array $shipping_classes = [], ?string $currency = null ): array {
		$rate_group = [
			'singleValue' => [ 'flatRate' => $this->mapi_price( (float) $location_rate->get_shipping_rate()->get_rate(), $currency ?? $this->currency ) ],
		];

		if ( ! empty( $shipping_classes ) ) {
			$rate_group['applicableShippingLabels'] = array_values( $shipping_classes );
		}

		return $rate_group;
	}

	/**
	 * @param array $rates_collections
	 *
	 * @throws InvalidValue If any of the objects in the array is not an instance of CountryRatesCollection.
	 */
	protected function validate_rates_collections( array $rates_collections ): void {
		array_walk(
			$rates_collections,
			function ( $obj ) {
				if ( ! $obj instanceof CountryRatesCollection ) {
					throw new InvalidValue( 'All values of the "rates_collections" array must be an instance of CountryRatesCollection.' );
				}
			}
		);
	}
}
