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
	 * We round to the nearest integer to avoid floating point precision issues.
	 * For e.g 33.3, we want to get 33300000 and not 33299999 which can cause
	 * the Google Ads API to throw the NON_MULTIPLE_OF_MINIMUM_CURRENCY_UNIT error.
	 *
	 * @param float $num Number to convert to micro units.
	 *
	 * @return int
	 */
	protected function to_micro( float $num ): int {
		return (int) round( $num * self::$micro );
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
