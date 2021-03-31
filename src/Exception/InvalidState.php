<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidState extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid state is requested.
	 *
	 * @param string $state
	 *
	 * @return InvalidState
	 */
	public static function from_state( string $state ): InvalidState {
		return new static( sprintf( 'The state %s is not valid.', $state ) );
	}
}
