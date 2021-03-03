<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Class for mapping between a status number and a status label.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 */
abstract class StatusMapping {

	/**
	 * Return the status as a label.
	 *
	 * @param int $number Status number.
	 *
	 * @return string
	 */
	public static function label( int $number ): string {
		return isset( static::MAPPING[ $number ] ) ? static::MAPPING[ $number ] : '';
	}

	/**
	 * Return the status as a number.
	 *
	 * @param string $label Status label.
	 *
	 * @return int
	 */
	public static function number( string $label ): int {
		$key = array_search( $label, static::MAPPING, true );
		return $key ?? 0;
	}

	/**
	 * Return all the status labels.
	 *
	 * @return array
	 */
	public static function labels(): array {
		return array_values( static::MAPPING );
	}
}
