<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

defined( 'ABSPATH' ) || exit;

/**
 * Interface with specific options for mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
interface WithMappingInterface {

	/**
	 * Returns the attribute name
	 *
	 * @return string
	 */
	public static function get_name(): string;

	/**
	 * Returns the available attribute sources
	 *
	 * @return array
	 */
	public static function get_sources(): array;

}
