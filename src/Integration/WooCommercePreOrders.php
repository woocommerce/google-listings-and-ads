<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use DateTimeZone;
use Exception;
use WC_DateTime;
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
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * WooCommercePreOrders constructor.
	 *
	 * @param ProductHelper $product_helper
	 */
	public function __construct( ProductHelper $product_helper ) {
		$this->product_helper = $product_helper;
	}

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

			$availability_date = $this->get_availability_datetime( $product );
			if ( ! empty( $availability_date ) ) {
				$attributes['availabilityDate'] = (string) $availability_date;
			}
		}

		return $attributes;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return WC_DateTime|null
	 */
	protected function get_availability_datetime( WC_Product $product ): ?WC_DateTime {
		$product   = $this->product_helper->maybe_swap_for_parent( $product );
		$timestamp = $product->get_meta( '_wc_pre_orders_availability_datetime', true );

		if ( empty( $timestamp ) ) {
			return null;
		}

		try {
			return new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

			return null;
		}
	}
}
