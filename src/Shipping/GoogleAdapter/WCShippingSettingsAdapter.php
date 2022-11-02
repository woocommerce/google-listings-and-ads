<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ServiceRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingLocation;
use Google\Service\ShoppingContent\PostalCodeGroup;
use Google\Service\ShoppingContent\PostalCodeRange;
use Google\Service\ShoppingContent\Price;
use Google\Service\ShoppingContent\RateGroup;
use Google\Service\ShoppingContent\Service as GoogleShippingService;
use Google\Service\ShoppingContent\Value;

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
	 * Parses the already validated input data and maps the provided shipping rates into MC shipping settings.
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
	 *
	 * @link AbstractShippingSettingsAdapter::mapTypes() The $data input comes from this method.
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
	 * Remove the extra data we added to the input array since the MC API doesn't expect them (and it will fail).
	 *
	 * @param array $data
	 */
	protected function unset_gla_data( array &$data ): void {
		unset( $data['rates_collections'] );
		parent::unset_gla_data( $data );
	}

	/**
	 * Map the collections of location rates for each country to the shipping settings.
	 *
	 * @param CountryRatesCollection[] $rates_collections
	 *
	 * @return void
	 */
	protected function map_rates_collections( array $rates_collections ) {
		$postcode_groups = [];
		$services        = [];
		foreach ( $rates_collections as $rates_collection ) {
			$postcode_groups = array_merge( $postcode_groups, $this->get_location_rates_postcode_groups( $rates_collection->get_location_rates() ) );

			foreach ( $rates_collection->get_rates_grouped_by_service() as $service_collection ) {
				$services[] = $this->create_shipping_service( $service_collection );
			}
		}

		$this->setServices( $services );
		$this->setPostalCodeGroups( array_values( $postcode_groups ) );
	}

	/**
	 * @param LocationRate[] $location_rates
	 * @param string         $shipping_area
	 * @param array          $applicable_classes
	 *
	 * @return RateGroup
	 *
	 * @throws InvalidArgument If an invalid value is provided for the shipping_area argument.
	 */
	protected function create_rate_group( array $location_rates, string $shipping_area, array $applicable_classes = [] ): RateGroup {
		switch ( $shipping_area ) {
			case ShippingLocation::COUNTRY_AREA:
				// Each country can only have one global rate.
				$country_rate = $location_rates[ array_key_first( $location_rates ) ];
				$rate_group   = $this->create_single_value_rate_group( $country_rate, $applicable_classes );
				break;
			case ShippingLocation::POSTCODE_AREA:
				$rate_group = new PostcodesRateGroupAdapter(
					[
						'location_rates'           => $location_rates,
						'currency'                 => $this->currency,
						'applicableShippingLabels' => $applicable_classes,
					]
				);
				break;
			case ShippingLocation::STATE_AREA:
				$rate_group = new StatesRateGroupAdapter(
					[
						'location_rates'           => $location_rates,
						'currency'                 => $this->currency,
						'applicableShippingLabels' => $applicable_classes,
					]
				);
				break;
			default:
				throw new InvalidArgument( 'Invalid shipping area.' );
		}

		return $rate_group;
	}

	/**
	 * Create a shipping service object.
	 *
	 * @param ServiceRatesCollection $service_collection
	 *
	 * @return GoogleShippingService
	 */
	protected function create_shipping_service( ServiceRatesCollection $service_collection ): GoogleShippingService {
		$rate_groups   = [];
		$shipping_area = $service_collection->get_shipping_area();
		foreach ( $service_collection->get_rates_grouped_by_shipping_class() as $class => $location_rates ) {
			$applicable_classes    = ! empty( $class ) ? [ $class ] : [];
			$rate_groups[ $class ] = $this->create_rate_group( $location_rates, $shipping_area, $applicable_classes );
		}

		$country = $service_collection->get_country();
		$name    = sprintf(
		/* translators: %1 is a random 4-digit string, %2 is the country code  */
			__( '[%1$s] Google Listings and Ads generated service - %2$s', 'google-listings-and-ads' ),
			sprintf( '%04x', mt_rand( 0, 0xffff ) ),
			$country
		);

		$service = new GoogleShippingService(
			[
				'active'          => true,
				'deliveryCountry' => $country,
				'currency'        => $this->currency,
				'name'            => $name,
				'deliveryTime'    => $this->get_delivery_time( $country ),
				'rateGroups'      => array_values( $rate_groups ),
			]
		);

		$min_order_amount = $service_collection->get_min_order_amount();
		if ( $min_order_amount ) {
			$min_order_value = new Price(
				[
					'currency' => $this->currency,
					'value'    => $min_order_amount,
				]
			);
			$service->setMinimumOrderValue( $min_order_value );
		}

		return $service;
	}

	/**
	 * Extract and return the postcode groups for the given location rates.
	 *
	 * @param LocationRate[] $location_rates
	 *
	 * @return PostalCodeGroup[]
	 */
	protected function get_location_rates_postcode_groups( array $location_rates ): array {
		$postcode_groups = [];

		foreach ( $location_rates as $location_rate ) {
			$location = $location_rate->get_location();
			if ( empty( $location->get_shipping_region() ) ) {
				continue;
			}
			$region = $location->get_shipping_region();

			$postcode_ranges = [];
			foreach ( $region->get_postcode_ranges() as $postcode_range ) {
				$postcode_ranges[] = new PostalCodeRange(
					[
						'postalCodeRangeBegin' => $postcode_range->get_start_code(),
						'postalCodeRangeEnd'   => $postcode_range->get_end_code(),
					]
				);
			}

			$postcode_groups[ $region->get_id() ] = new PostalCodeGroup(
				[
					'name'             => $region->get_id(),
					'country'          => $location->get_country(),
					'postalCodeRanges' => $postcode_ranges,
				]
			);
		}

		return $postcode_groups;
	}

	/**
	 * @param LocationRate $location_rate
	 * @param string[]     $shipping_classes
	 *
	 * @return RateGroup
	 */
	protected function create_single_value_rate_group( LocationRate $location_rate, array $shipping_classes = [] ): RateGroup {
		$price = new Price(
			[
				'currency' => $this->currency,
				'value'    => $location_rate->get_shipping_rate()->get_rate(),
			]
		);

		return new RateGroup(
			[
				'singleValue'              => new Value( [ 'flatRate' => $price ] ),
				'applicableShippingLabels' => $shipping_classes,
			]
		);
	}

	/**
	 * @param array $rates_collections
	 *
	 * @throws InvalidClass If any of the objects in the array is not an instance of CountryRatesCollection.
	 */
	protected function validate_rates_collections( array $rates_collections ) {
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
