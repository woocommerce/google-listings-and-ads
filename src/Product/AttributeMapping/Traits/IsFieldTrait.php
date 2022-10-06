<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Trait for enums
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits
 */
trait IsFieldTrait {

	/**
	 * Returns false for the is_enum property
	 *
	 * @return false
	 */
	public static function is_enum(): bool {
		return false;
	}

	/**
	 * Returns the attribute sources
	 *
	 * @return array The available sources
	 */
	public static function get_sources(): array {
		return AttributeMappingHelper::get_fields( self::get_id() );
	}
}
