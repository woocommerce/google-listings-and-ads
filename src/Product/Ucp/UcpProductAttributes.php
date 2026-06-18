<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\CustomAttribute;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class UcpProductAttributes
 *
 * Adds Google UCP (Universal Commerce Protocol) eligibility attributes to products as they are
 * adapted for the Merchant Center feed.
 *
 * Google UCP reads the existing Merchant Center feed; products opt in to agentic checkout via feed
 * attributes (`native_commerce`, `merchant_item_id`, and — for regulated items — `consumer_notice`).
 * These are not typed fields on the Content API product resource, so they are attached as
 * `customAttributes`.
 *
 * DRAFT / WOOAI-634 — open questions captured in the PR description:
 *  - Content API custom attributes vs. Merchant API supplemental data source for UCP eligibility.
 *  - `consumer_notice` (nested groupValues) is not yet emitted — needs a data source for the notice.
 *  - The per-product `woocommerce_agentic_commerce_should_sync_product` filter's canonical home is
 *    still being decided (WOOAI-636); it is consulted here with a default of true.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Ucp
 */
class UcpProductAttributes implements Service, Registerable {

	/**
	 * Register the feed hook.
	 *
	 * The service always registers; enablement is checked at runtime in the callback so load order
	 * between this plugin and woocommerce-ai does not matter.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_gla_product_attribute_values',
			[ $this, 'add_ucp_attributes' ],
			20,
			3
		);
	}

	/**
	 * Attach UCP eligibility custom attributes to the adapted Google product.
	 *
	 * Hooked on `woocommerce_gla_product_attribute_values`. The adapter is mutated directly (it is the
	 * Google product object); the `$attributes` override array is returned unchanged.
	 *
	 * @param array            $attributes Attribute overrides for the product (passed through untouched).
	 * @param WC_Product       $product    The WooCommerce product being adapted.
	 * @param WCProductAdapter $adapter    The Google product adapter.
	 *
	 * @return array
	 */
	public function add_ucp_attributes( array $attributes, WC_Product $product, WCProductAdapter $adapter ): array {
		if ( ! UcpEnablement::is_enabled() ) {
			return $attributes;
		}

		if ( ! $this->is_product_ucp_eligible( $product ) ) {
			// Not eligible: emit nothing. The product is re-submitted in full each sync, so a product
			// that becomes ineligible simply loses `native_commerce` on the next sync.
			return $attributes;
		}

		$this->set_custom_attribute( $adapter, 'native_commerce', 'true' );
		$this->set_custom_attribute( $adapter, 'merchant_item_id', (string) $product->get_id() );

		// TODO (WOOAI-634): emit `consumer_notice` for regulated items once a notice data source exists.

		return $attributes;
	}

	/**
	 * Whether a given product should be exposed to agentic commerce / UCP.
	 *
	 * @param WC_Product $product The WooCommerce product.
	 *
	 * @return bool
	 */
	protected function is_product_ucp_eligible( WC_Product $product ): bool {
		/**
		 * Filters whether a product opts in to agentic commerce / UCP eligibility.
		 *
		 * Canonical definition/home is being decided in WOOAI-636. Defaults to true once agentic
		 * commerce is enabled site-wide.
		 *
		 * @param bool       $should_sync Whether the product should be UCP-eligible.
		 * @param WC_Product $product     The WooCommerce product.
		 */
		return (bool) apply_filters( 'woocommerce_agentic_commerce_should_sync_product', true, $product );
	}

	/**
	 * Append a single custom attribute to the adapted product, preserving any existing ones.
	 *
	 * @param WCProductAdapter $adapter The Google product adapter.
	 * @param string           $name    Attribute name.
	 * @param string           $value   Attribute value.
	 */
	protected function set_custom_attribute( WCProductAdapter $adapter, string $name, string $value ): void {
		$custom_attributes = $adapter->getCustomAttributes() ?? [];

		$attribute = new CustomAttribute();
		$attribute->setName( $name );
		$attribute->setValue( $value );

		$custom_attributes[] = $attribute;
		$adapter->setCustomAttributes( $custom_attributes );
	}
}
