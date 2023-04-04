<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Shipping_Zone;

defined( 'ABSPATH' ) || exit;

/**
 * Class ZoneLocationsParser
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
 */
class ZoneLocationsParser implements Service {

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * ZoneLocationsParser constructor.
	 *
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( GoogleHelper $google_helper ) {
		$this->google_helper = $google_helper;
	}

	/**
	 * Returns the supported locations for the given WooCommerce shipping zone.
	 *
	 * @param WC_Shipping_Zone $zone
	 *
	 * @return ShippingLocation[] Array of supported locations.
	 */
	public function parse( WC_Shipping_Zone $zone ): array {
		$locations = [];
		$postcodes = $this->get_postcodes_from_zone( $zone );

		foreach ( $zone->get_zone_locations() as $location ) {
			switch ( $location->type ) {
				case 'country':
					$country = $location->code;
					if ( $this->google_helper->is_country_supported( $country ) ) {
						$google_id = $this->google_helper->find_country_id_by_code( $country );
						$region    = $this->maybe_create_region_for_postcodes( $country, $postcodes );

						$locations[ $location->code ] = new ShippingLocation( $google_id, $country, null, $region );
					}
					break;
				case 'continent':
					foreach ( $this->google_helper->get_supported_countries_from_continent( $location->code ) as $country ) {
						$google_id = $this->google_helper->find_country_id_by_code( $country );
						$region    = $this->maybe_create_region_for_postcodes( $country, $postcodes );

						$locations[ $country ] = new ShippingLocation( $google_id, $country, null, $region );
					}
					break;
				case 'state':
					[ $country, $state ] = explode( ':', $location->code );

					// Ignore if the country is not supported.
					if ( ! $this->google_helper->is_country_supported( $country ) ) {
						break;
					}

					$region = $this->maybe_create_region_for_postcodes( $country, $postcodes );

					// Only add the state if the regional shipping is supported for the country.
					if ( $this->google_helper->does_country_support_regional_shipping( $country ) ) {
						$google_id = $this->google_helper->find_subdivision_id_by_code( $state, $country );

						if ( ! is_null( $google_id ) ) {
							$locations[ $location->code ] = new ShippingLocation( $google_id, $country, $state, $region );
						}
					} else {
						$google_id             = $this->google_helper->find_country_id_by_code( $country );
						$locations[ $country ] = new ShippingLocation( $google_id, $country, null, $region );
					}
					break;
				default:
					break;
			}
		}

		return array_values( $locations );
	}

	/**
	 * Returns the applicable postcodes for the given WooCommerce shipping zone.
	 *
	 * @param WC_Shipping_Zone $zone
	 *
	 * @return PostcodeRange[] Array of postcodes.
	 */
	protected function get_postcodes_from_zone( WC_Shipping_Zone $zone ): array {
		$postcodes = array_filter(
			$zone->get_zone_locations(),
			function ( $location ) {
				return 'postcode' === $location->type;
			}
		);

		return array_map(
			function ( $postcode ) {
				return PostcodeRange::from_string( $postcode->code );
			},
			$postcodes
		);
	}

	/**
	 * Returns the applicable shipping region including postcodes for the given WooCommerce shipping zone.
	 *
	 * @param string $country
	 * @param array  $postcode_ranges
	 *
	 * @return ShippingRegion|null
	 */
	protected function maybe_create_region_for_postcodes( string $country, array $postcode_ranges ): ?ShippingRegion {
		// Do not return a region if the country does not support regional shipping, or if no postcode ranges provided.
		if ( ! $this->google_helper->does_country_support_regional_shipping( $country ) || empty( $postcode_ranges ) ) {
			return null;
		}

		$region_id = ShippingRegion::generate_random_id();

		return new ShippingRegion( $region_id, $country, $postcode_ranges );
	}

}
