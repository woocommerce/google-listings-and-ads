<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class InvalidType
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidType extends InvalidArgumentException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when an invalid type is provided.
	 *
	 * @param string   $type
	 * @param string[] $valid_types
	 *
	 * @return InvalidType
	 */
	public static function from_type( string $type, array $valid_types ): InvalidType {
		return new static(
			sprintf(
				'Invalid type "%s". Valid types are: "%s"',
				$type,
				join( '", "', $valid_types )
			)
		);
	}
}
