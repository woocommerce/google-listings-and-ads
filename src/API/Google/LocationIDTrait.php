<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsCountryQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidState;
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
	 * Mapping data for location IDs.
	 *
	 * @see https://developers.google.com/adwords/api/docs/appendix/geotargeting
	 *
	 * @var string[]
	 */
	protected $mapping = [
		'AL' => 21133,
		'AK' => 21132,
		'AZ' => 21136,
		'AR' => 21135,
		'CA' => 21137,
		'CO' => 21138,
		'CT' => 21139,
		'DE' => 21141,
		'DC' => 21140,
		'FL' => 21142,
		'GA' => 21143,
		'HI' => 21144,
		'ID' => 21146,
		'IL' => 21147,
		'IN' => 21148,
		'IA' => 21145,
		'KS' => 21149,
		'KY' => 21150,
		'LA' => 21151,
		'ME' => 21154,
		'MD' => 21153,
		'MA' => 21152,
		'MI' => 21155,
		'MN' => 21156,
		'MS' => 21158,
		'MO' => 21157,
		'MT' => 21159,
		'NE' => 21162,
		'NV' => 21166,
		'NH' => 21163,
		'NJ' => 21164,
		'NM' => 21165,
		'NY' => 21167,
		'NC' => 21160,
		'ND' => 21161,
		'OH' => 21168,
		'OK' => 21169,
		'OR' => 21170,
		'PA' => 21171,
		'RI' => 21172,
		'SC' => 21173,
		'SD' => 21174,
		'TN' => 21175,
		'TX' => 21176,
		'UT' => 21177,
		'VT' => 21179,
		'VA' => 21178,
		'WA' => 21180,
		'WV' => 21183,
		'WI' => 21182,
		'WY' => 21184,
	];

	/**
	 * Get the location ID for a given state.
	 *
	 * @param string $state
	 *
	 * @return int
	 * @throws InvalidState When the provided state is not found in the mapping.
	 */
	protected function get_state_id( string $state ): int {
		if ( ! array_key_exists( $state, $this->mapping ) ) {
			throw InvalidState::from_state( $state );
		}

		return $this->mapping[ $state ];
	}

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
