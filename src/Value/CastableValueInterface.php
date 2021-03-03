<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Interface CastableValueInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
interface CastableValueInterface {

	/**
	 * Cast a value and return a new instance of the class.
	 *
	 * @param mixed $value Mixed value to cast to class type.
	 *
	 * @return self
	 */
	public static function cast( $value );
}
