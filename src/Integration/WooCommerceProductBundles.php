<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use WC_Product;
use WC_Product_Bundle;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerceProductBundles
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WooCommerceProductBundles implements IntegrationInterface {

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * WooCommerceProductBundles constructor.
	 *
	 * @param AttributeManager $attribute_manager
	 */
	public function __construct( AttributeManager $attribute_manager ) {
		$this->attribute_manager = $attribute_manager;
	}

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return class_exists( 'WC_Bundles' ) && is_callable( 'WC_Bundles::instance' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_product_types();

		// update the isBundle attribute for bundle products
		add_action(
			'woocommerce_new_product',
			function ( int $product_id, WC_Product $product ) {
				$this->handle_update_product( $product );
			},
			10,
			2
		);
		add_action(
			'woocommerce_update_product',
			function ( int $product_id, WC_Product $product ) {
				$this->handle_update_product( $product );
			},
			10,
			2
		);

		// recalculate the product price for bundles
		add_filter(
			'woocommerce_gla_product_attribute_value_price',
			function ( float $price, WC_Product $product, bool $tax_excluded ) {
				return $this->calculate_price( $price, $product, $tax_excluded );
			},
			10,
			3
		);
		add_filter(
			'woocommerce_gla_product_attribute_value_sale_price',
			function ( float $sale_price, WC_Product $product, bool $tax_excluded ) {
				return $this->calculate_sale_price( $sale_price, $product, $tax_excluded );
			},
			10,
			3
		);

		// adapt the `is_virtual` property for bundle products
		add_filter(
			'woocommerce_gla_product_property_value_is_virtual',
			function ( bool $is_virtual, WC_Product $product ) {
				return $this->is_virtual_bundle( $is_virtual, $product );
			},
			10,
			2
		);

		// filter unsupported bundle products
		add_filter(
			'woocommerce_gla_get_sync_ready_products_pre_filter',
			function ( array $products ) {
				return $this->get_sync_ready_bundle_products( $products );
			}
		);
	}

	/**
	 * Adds the "bundle" product type to the list of applicable types
	 * for every attribute that can be applied to "simple" products.
	 *
	 * @return void
	 */
	protected function init_product_types(): void {
		// every attribute that applies to simple products also applies to bundle products.
		foreach ( AttributeManager::get_available_attribute_types() as $attribute_type ) {
			$attribute_id     = call_user_func( [ $attribute_type, 'get_id' ] );
			$applicable_types = call_user_func( [ $attribute_type, 'get_applicable_product_types' ] );
			if ( ! in_array( 'simple', $applicable_types, true ) ) {
				continue;
			}

			add_filter(
				"woocommerce_gla_attribute_applicable_product_types_{$attribute_id}",
				function ( array $applicable_types ) {
					return $this->add_bundle_type( $applicable_types );
				}
			);
		}

		// hide the isBundle attribute on 'bundle' products (we set it automatically to true)
		add_filter(
			'woocommerce_gla_attribute_hidden_product_types_isBundle',
			function ( array $applicable_types ) {
				return $this->add_bundle_type( $applicable_types );
			}
		);

		// add the 'bundle' type to list of supported product types
		add_filter(
			'woocommerce_gla_supported_product_types',
			function ( array $product_types ) {
				return $this->add_bundle_type( $product_types );
			}
		);
	}

	/**
	 * @param array $types
	 *
	 * @return array
	 */
	private function add_bundle_type( array $types ): array {
		$types[] = 'bundle';

		return $types;
	}

	/**
	 * Set the isBundle product attribute to 'true' if product is a bundle.
	 *
	 * @param WC_Product $product
	 */
	private function handle_update_product( WC_Product $product ) {
		if ( $product->is_type( 'bundle' ) ) {
			$this->attribute_manager->update( $product, new IsBundle( true ) );
		}
	}

	/**
	 * @param float      $price        Calculated price of the product
	 * @param WC_Product $product      WooCommerce product
	 * @param bool       $tax_excluded Whether tax is excluded from product price
	 */
	private function calculate_price( float $price, WC_Product $product, bool $tax_excluded ): float {
		if ( ! $product instanceof WC_Product_Bundle ) {
			return $price;
		}

		return $tax_excluded ? (float) $product->get_bundle_regular_price_excluding_tax() : (float) $product->get_bundle_regular_price_including_tax();
	}

	/**
	 * @param float      $sale_price   Calculated sale price of the product
	 * @param WC_Product $product      WooCommerce product
	 * @param bool       $tax_excluded Whether tax is excluded from product price
	 */
	private function calculate_sale_price( float $sale_price, WC_Product $product, bool $tax_excluded ): float {
		if ( ! $product instanceof WC_Product_Bundle ) {
			return $sale_price;
		}

		$regular_price = $tax_excluded ? (float) $product->get_bundle_regular_price_excluding_tax() : (float) $product->get_bundle_regular_price_including_tax();
		$price         = $tax_excluded ? (float) $product->get_bundle_price_excluding_tax() : (float) $product->get_bundle_price_including_tax();

		// return current price as the sale price if it's lower than the regular price.
		if ( $price < $regular_price ) {
			return $price;
		}

		return $sale_price;
	}

	/**
	 * @param bool       $is_virtual Whether a product is virtual
	 * @param WC_Product $product    WooCommerce product
	 */
	private function is_virtual_bundle( bool $is_virtual, WC_Product $product ): bool {
		if ( $product instanceof WC_Product_Bundle && is_callable( [ $product, 'is_virtual_bundle' ] ) ) {
			return $product->is_virtual_bundle();
		}

		return $is_virtual;
	}

	/**
	 * Skip unsupported bundle products.
	 *
	 * @param WC_Product[] $products WooCommerce products
	 */
	private function get_sync_ready_bundle_products( array $products ): array {
		return array_filter(
			$products,
			function ( WC_Product $product ) {
				if ( $product instanceof WC_Product_Bundle && $product->requires_input() ) {
					return false;
				}

				return true;
			}
		);
	}
}
