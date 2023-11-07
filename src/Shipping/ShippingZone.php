<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingZone
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 1.9.0
 */
class ShippingZone implements Service {

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var ZoneLocationsParser
	 */
	protected $locations_parser;

	/**
	 * @var ZoneMethodsParser
	 */
	protected $methods_parser;

	/**
	 * @var LocationRatesProcessor
	 */
	protected $rates_processor;

	/**
	 * @var array[][]|null Array of shipping rates for each location.
	 */
	protected $location_rates = null;

	/**
	 * ShippingZone constructor.
	 *
	 * @param WC                     $wc
	 * @param ZoneLocationsParser    $location_parser
	 * @param ZoneMethodsParser      $methods_parser
	 * @param LocationRatesProcessor $rates_processor
	 */
	public function __construct(
		WC $wc,
		ZoneLocationsParser $location_parser,
		ZoneMethodsParser $methods_parser,
		LocationRatesProcessor $rates_processor
	) {
		$this->wc               = $wc;
		$this->locations_parser = $location_parser;
		$this->methods_parser   = $methods_parser;
		$this->rates_processor  = $rates_processor;
	}

	/**
	 * Gets the shipping countries from the WooCommerce shipping zones.
	 *
	 * Note: This method only returns the countries that have at least one shipping method.
	 *
	 * @return string[]
	 */
	public function get_shipping_countries(): array {
		$this->parse_shipping_zones();

		$countries = array_keys( $this->location_rates );

		return array_values( $countries );
	}

	/**
	 * Returns the available shipping rates for a country and its subdivisions.
	 *
	 * @param string $country_code
	 *
	 * @return LocationRate[]
	 */
	public function get_shipping_rates_for_country( string $country_code ): array {
		$this->parse_shipping_zones();

		if ( empty( $this->location_rates[ $country_code ] ) ) {
			return [];
		}

		// Process the rates for each country subdivision separately.
		$location_rates = array_map( [ $this->rates_processor, 'process' ], $this->location_rates[ $country_code ] );

		// Convert the string array keys to integers.
		$country_rates = array_values( $location_rates );

		// Flatten and merge the country shipping rates.
		$country_rates = array_merge( [], ...$country_rates );

		return array_values( $country_rates );
	}

	/**
	 * Returns the available shipping rates for a country.
	 *
	 * If there are separate rates for the country's subdivisions (e.g. state,province, postcode etc.), they will be
	 * grouped by their parent country.
	 *
	 * @param string $country_code
	 *
	 * @return LocationRate[]
	 */
	public function get_shipping_rates_grouped_by_country( string $country_code ): array {
		$this->parse_shipping_zones();

		if ( empty( $this->location_rates[ $country_code ] ) ) {
			return [];
		}

		// Convert the string array keys to integers.
		$country_rates = array_values( $this->location_rates[ $country_code ] );

		// Flatten and merge the country shipping rates.
		$country_rates = array_merge( [], ...$country_rates );

		return $this->rates_processor->process( $country_rates );
	}

	/**
	 * Parses the WooCommerce shipping zones.
	 */
	protected function parse_shipping_zones(): void {
		// Don't parse if already parsed.
		if ( null !== $this->location_rates ) {
			return;
		}
		$this->location_rates = [];

		foreach ( $this->wc->get_shipping_zones() as $zone ) {
			$zone           = $this->wc->get_shipping_zone( $zone['zone_id'] );
			$zone_locations = $this->locations_parser->parse( $zone );
			$shipping_rates = $this->methods_parser->parse( $zone );
			$this->map_rates_to_locations( $shipping_rates, $zone_locations );
		}
	}

	/**
	 * Maps each shipping method to its related shipping locations.
	 *
	 * @param ShippingRate[]     $shipping_rates The shipping rates.
	 * @param ShippingLocation[] $locations      The shipping locations.
	 *
	 * @since 2.1.0
	 */
	protected function map_rates_to_locations( array $shipping_rates, array $locations ): void {
		if ( empty( $shipping_rates ) || empty( $locations ) ) {
			return;
		}

		foreach ( $locations as $location ) {
			$location_rates = [];
			foreach ( $shipping_rates as $shipping_rate ) {
				$location_rates[] = new LocationRate( $location, $shipping_rate );
			}

			$country_code = $location->get_country();

			// Initialize the array if it doesn't exist.
			$this->location_rates[ $country_code ] = $this->location_rates[ $country_code ] ?? [];

			$location_key = (string) $location;

			// Group the rates by their parent country and a location key. The location key is used to prevent duplicate rates for the same location.
			$this->location_rates[ $country_code ][ $location_key ] = $location_rates;
		}
	}
}
