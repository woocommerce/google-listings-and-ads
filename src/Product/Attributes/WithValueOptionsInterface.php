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
	 * @return array
	 */
	public function get_value_options(): array;
}
