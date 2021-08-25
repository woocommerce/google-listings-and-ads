<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidTerm
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidTerm extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a text contains invalid terms.
	 *
	 * @param string $text
	 *
	 * @return InvalidTerm
	 */
	public static function contains_invalid_terms( string $text ): InvalidTerm {
		return new static( sprintf( 'The text "%s" contains invalid terms.', $text ) );
	}
}
