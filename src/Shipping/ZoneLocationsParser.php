<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Shipping_Zone;

defined( 'ABSPATH' ) || exit;

/**
 * Class ZoneLocationsParser
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class ZoneLocationsParser implements Service {

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * ZoneLocationsParser constructor.
	 *
	 * @param WC           $wc
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( WC $wc, GoogleHelper $google_helper ) {
		$this->wc            = $wc;
		$this->google_helper = $google_helper;
	}

	/**
	 * Returns the supported locations for the given WooCommerce shipping zone.
	 *
	 * @param WC_Shipping_Zone $zone
	 *
	 * @return Location[] Array of supported locations.
	 */
	public function parse( WC_Shipping_Zone $zone ): array {
		$locations = [];
		$postcodes = $this->get_postcodes_from_zone( $zone );

		foreach ( $zone->get_zone_locations() as $location ) {
			switch ( $location->type ) {
				case 'country':
					if ( $this->google_helper->is_country_supported( $location->code ) ) {
						$locations[ $location->code ] = new Location( $location->code, null, $postcodes );
					}
					break;
				case 'continent':
					foreach ( $this->get_supported_countries_from_continent( $location->code ) as $country ) {
						$locations[ $country ] = new Location( $country, null, $postcodes );
					}
					break;
				case 'state':
					[ $country, $state ] = explode( ':', $location->code );

					// Ignore if the country is not supported.
					if ( ! $this->google_helper->is_country_supported( $country ) ) {
						break;
					}

					// Only add the state if the regional shipping is supported for its country.
					if ( $this->google_helper->does_country_support_regional_shipping( $country ) ) {
						$locations[ $location->code ] = new Location( $country, $state, $postcodes );
					} else {
						$locations[ $country ] = new Location( $country, null, $postcodes );
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
	 * @return string[] Array of postcodes.
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
				return $postcode->code;
			},
			$postcodes
		);
	}

	/**
	 * Gets the list of supported Merchant Center countries from a continent.
	 *
	 * @param string $continent_code
	 *
	 * @return string[] Returns an array of country codes with each country code used both as the key and value.
	 *                  For example: [ 'US' => 'US', 'DE' => 'DE' ].
	 */
	protected function get_supported_countries_from_continent( string $continent_code ): array {
		$countries  = [];
		$continents = $this->wc->get_wc_countries()->get_continents();
		if ( isset( $continents[ $continent_code ] ) ) {
			$countries = $continents[ $continent_code ]['countries'];

			// Match the list of countries with the list of Merchant Center supported countries.
			$countries = array_intersect( $countries, $this->google_helper->get_mc_supported_countries() );

			// Use the country code as array keys.
			$countries = array_combine( $countries, $countries );
		}

		return $countries;
	}

}
