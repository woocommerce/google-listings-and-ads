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
			// A country with rates but no shipping time is left out entirely,
			// prices and regions alike, with an error, so one bad country
			// cannot cancel the whole update and no region is sent without
			// the service it belongs to.
			if ( ! $this->has_delivery_time( $rates_collection->get_country() ) ) {
				$this->report_country_missing_delivery_time( $rates_collection->get_country() );
				continue;
			}

			// array_replace, not array_merge: region ids are numeric strings and
			// array_merge would renumber them, breaking the table -> region reference.
			$this->regions = array_replace( $this->regions, $this->get_location_rates_regions( $rates_collection->get_location_rates() ) );

			// Every currency mapped to the country gets its own service, so
			// products priced in each currency find a currency-matching
			// shipping service.
			foreach ( $rates_collection->get_rates_grouped_by_service() as $service_collection ) {
				foreach ( $this->get_currencies_for_country( $rates_collection->get_country() ) as $service_currency ) {
					$service = $this->create_shipping_service( $service_collection, $service_currency );

					if ( null !== $service ) {
						$this->services[] = $service;
					}
				}
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
	 * Create a shipping service in the given currency.
	 *
	 * Rate group prices must be in the same currency as the service they
	 * belong to, so for a non-store currency every rate amount and the
	 * minimum order value are converted before the service is built. When
	 * any amount cannot be converted the whole service is left out (null)
	 * with an error, so a store-currency amount is never sent under a
	 * non-store-currency service.
	 *
	 * @param ServiceRatesCollection $service_collection
	 * @param string                 $currency ISO 4217 currency code of the service.
	 *
	 * @return array|null The service, or null when its amounts cannot be
	 *                    converted into the given currency.
	 */
	protected function create_shipping_service( ServiceRatesCollection $service_collection, string $currency ): ?array {
		$country = $service_collection->get_country();

		$rate_groups   = [];
		$shipping_area = $service_collection->get_shipping_area();
		foreach ( $service_collection->get_rates_grouped_by_shipping_class() as $class => $location_rates ) {
			$converted_rates = $this->convert_location_rates( $location_rates, $currency, $country );

			if ( null === $converted_rates ) {
				$this->report_country_missing_conversion( $country, $currency );

				return null;
			}

			$applicable_classes    = ! empty( $class ) ? [ $class ] : [];
			$rate_groups[ $class ] = $this->create_rate_group( $converted_rates, $shipping_area, $applicable_classes, $currency );
		}

		$min_order_amount = $service_collection->get_min_order_amount();
		if ( $min_order_amount ) {
			$min_order_amount = $this->convert_amount_for_service( (float) $min_order_amount, $currency, $country );

			if ( null === $min_order_amount ) {
				$this->report_country_missing_conversion( $country, $currency );

				return null;
			}
		}

		$service = [
			'serviceName'       => sprintf(
				/* translators: %1 is a random 4-digit string, %2 is the country code, %3 is the currency code */
				__( '[%1$s] Google for WooCommerce generated service - %2$s (%3$s)', 'google-listings-and-ads' ),
				sprintf( '%04x', wp_rand( 0, 0xffff ) ),
				$country,
				$currency
			),
			'active'            => true,
			// One service per country and currency; deliveryCountries is an array as MAPI requires.
			'deliveryCountries' => [ $country ],
			'currencyCode'      => $currency,
			'deliveryTime'      => $this->get_delivery_time( $country ),
			'shipmentType'      => 'DELIVERY',
			'rateGroups'        => array_values( $rate_groups ),
		];

		if ( $min_order_amount ) {
			$service['minimumOrderValue'] = $this->mapi_price( (float) $min_order_amount, $currency );
		}

		return $service;
	}

	/**
	 * Returns the location rates with every amount converted into the given
	 * currency, or the rates unchanged for the store currency. Returns null
	 * when any amount cannot be converted.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency ISO 4217 currency code of the service.
	 * @param string         $country        Country the service is built for.
	 *
	 * @return LocationRate[]|null
	 */
	protected function convert_location_rates( array $location_rates, string $currency, string $country ): ?array {
		if ( $currency === $this->currency ) {
			return $location_rates;
		}

		$converted = [];
		foreach ( $location_rates as $location_rate ) {
			$amount = $this->convert_amount_for_service( (float) $location_rate->get_shipping_rate()->get_rate(), $currency, $country );

			if ( null === $amount ) {
				return null;
			}

			$shipping_rate = clone $location_rate->get_shipping_rate();
			$shipping_rate->set_rate( $amount );

			$converted[] = new LocationRate( $location_rate->get_location(), $shipping_rate );
		}

		return $converted;
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
