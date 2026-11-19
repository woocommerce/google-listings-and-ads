<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCountryQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Google\ApiCore\ApiException;

defined( 'ABSPATH' ) || exit;

/**
 * Trait LocationIDTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait LocationIDTrait {

	/**
	 * Fetch location IDs from country codes.
	 *
	 * @param array $country_codes List of country codes to fetch.
	 *
	 * @return array Mapped array of location IDs to country codes.
	 */
	protected function get_location_ids( array $country_codes ): array {
		$cache_key = strtolower( join( '-', $country_codes ) );
		$transient = isset( $this->transients ) ? $this->transients->get( TransientsInterface::ADS_LOCATION_IDS ) : false;

		// Check if we have the location ID's cached in the transient.
		if ( $transient && ! empty( $transient[ $cache_key ] ) ) {
			return $transient[ $cache_key ];
		}

		try {
			// Query the location ID's from the Google Ads API.
			$location_results = ( new AdsCountryQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'geo_target_constant.country_code', $country_codes, 'IN' )
				->get_results();

			$locations = [];
			foreach ( $location_results->iterateAllElements() as $row ) {
				$location                        = $row->getGeoTargetConstant();
				$locations[ $location->getId() ] = $location->getCountryCode();
			}

			if ( isset( $this->transients ) ) {
				$this->transients->set( TransientsInterface::ADS_LOCATION_IDS, [ $cache_key => $locations ] );
			}

			return $locations;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
		}

		return [];
	}
}
