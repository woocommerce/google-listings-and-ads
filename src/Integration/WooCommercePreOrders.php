<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use WC_Pre_Orders_Product;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommercePreOrders
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 *
 * @link https://woocommerce.com/products/woocommerce-pre-orders/
 *
 * @since x.x.x
 */
class WooCommercePreOrders implements IntegrationInterface {

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WC_PRE_ORDERS_VERSION' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( array $attributes, WC_Product $product ) {
				return $this->maybe_set_preorder_availability( $attributes, $product );
			},
			2,
			10
		);
	}

	/**
	 * Sets the product's availability to "preorder" if it's in-stock and can be pre-ordered.
	 *
	 * @param array      $attributes
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	protected function maybe_set_preorder_availability( array $attributes, WC_Product $product ): array {
		if ( $product->is_in_stock() && WC_Pre_Orders_Product::product_can_be_pre_ordered( $product ) ) {
			$attributes['availability'] = WCProductAdapter::AVAILABILITY_PREORDER;

			$timestamp = WC_Pre_Orders_Product::get_localized_availability_datetime_timestamp( $product );
			if ( ! empty( $timestamp ) ) {
				// Convert the timestamp back to UTC.
				// The localized offset is already applied to timestamp by WC_Pre_Orders_Product.
				$utc_timestamp                  = $timestamp - wc_timezone_offset();
				$attributes['availabilityDate'] = gmdate( 'c', intval( $utc_timestamp ) );
			}
		}

		return $attributes;
	}
}
