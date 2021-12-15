<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\GoogleHelper;
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

	use GoogleHelper;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * ShippingZone constructor.
	 *
	 * @param WC $wc
	 */
	public function __construct( WC $wc ) {
		$this->wc = $wc;
	}

	/**
	 * Gets the shipping countries from the WooCommerce shipping zones.
	 *
	 * @return string[]
	 */
	public function get_shipping_countries(): array {
		$countries = [];
		foreach ( $this->wc->get_shipping_zones() as $zone ) {
			$zone = $this->wc->get_shipping_zone( $zone['zone_id'] );
			foreach ( $zone->get_zone_locations() as $location ) {
				switch ( $location->type ) {
					case 'country':
						$countries[ $location->code ] = $location->code;
						break;
					case 'continent':
						$countries = array_merge( $countries, $this->get_countries_from_continent( $location->code ) );
						break;
					case 'state':
						$country_code               = $this->get_country_of_state( $location->code );
						$countries[ $country_code ] = $country_code;
						break;
					default:
						break;
				}
			}
		}

		// Match the list of shipping countries with the list of Merchant Center supported countries.
		$countries = array_intersect( $countries, $this->get_mc_supported_countries() );

		return array_values( $countries );
	}

	/**
	 * Gets the list of countries from a continent.
	 *
	 * @param string $continent_code
	 *
	 * @return string[] Returns an array of country codes with each country code used both as the key and value.
	 *                  For example: [ 'US' => 'US', 'DE' => 'DE' ].
	 */
	protected function get_countries_from_continent( string $continent_code ): array {
		$countries  = [];
		$continents = $this->wc->get_wc_countries()->get_continents();
		if ( isset( $continents[ $continent_code ] ) ) {
			$countries = $continents[ $continent_code ]['countries'];
			// Use the country code as array keys.
			$countries = array_combine( $countries, $countries );
		}

		return $countries;
	}

	/**
	 * Gets the country code of a state defined in WooCommerce shipping zone.
	 *
	 * @param string $state_code
	 *
	 * @return string
	 */
	protected function get_country_of_state( string $state_code ): string {
		$location_codes = explode( ':', $state_code );

		return $location_codes[0];
	}
}
