<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidState;

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
}
