<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Interface WithValueOptionsInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
interface WithValueOptionsInterface {
	/**
	 * Return an array of values available to choose for the attribute.
	 *
	 * Note: array key is used as the option key.
	 *
	 * @return array
	 */
	public static function get_value_options(): array;
}
