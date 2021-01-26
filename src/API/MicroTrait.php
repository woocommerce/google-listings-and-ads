<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MicroTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API
 */
trait MicroTrait {

	/**
	 * Micro units.
	 *
	 * @var integer
	 */
	protected static $micro = 1000000;

	/**
	 * Convert to micro units.
	 *
	 * @param float $num Number to convert to micro units.
	 *
	 * @return int
	 */
	protected function to_micro( float $num ): int {
		return (int) ( $num * self::$micro );
	}

	/**
	 * Convert from micro units.
	 *
	 * @param int $num Number to convert from micro units.
	 *
	 * @return float
	 */
	protected function from_micro( int $num ): float {
		return (float) ( $num / self::$micro );
	}
}
