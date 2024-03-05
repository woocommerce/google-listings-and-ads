<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes;

/**
 * Trait AttributesTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes
 */
trait AttributesTrait {
	/**
	 * Return an array of WooCommerce product types that the Google Listings and Ads tab can be displayed for.
	 *
	 * @return array of WooCommerce product types (e.g. 'simple', 'variable', etc.)
	 */
	protected function get_applicable_product_types(): array {
		return apply_filters( 'woocommerce_gla_attributes_tab_applicable_product_types', [ 'simple', 'variable' ] );
	}
}
