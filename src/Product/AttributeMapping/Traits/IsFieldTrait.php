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
		return apply_filters(
			'woocommerce_gla_attribute_mapping_sources',
			array_merge(
				AttributeMappingHelper::get_source_product_fields(),
				AttributeMappingHelper::get_source_taxonomies(),
				AttributeMappingHelper::get_source_custom_attributes()
			),
			self::get_id()
		);
	}
}
