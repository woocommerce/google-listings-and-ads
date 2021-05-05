<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerceBrands
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WooCommerceBrands implements IntegrationInterface {

	protected const VALUE_KEY = 'woocommerce_brands';

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WC_BRANDS_VERSION' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter(
			'gla_product_attribute_value_options_brand',
			function ( array $value_options ) {
				return $this->add_value_option( $value_options );
			}
		);
		add_filter(
			'gla_product_attribute_value_brand',
			function ( $value, WC_Product $product ) {
				return $this->get_brand( $value, $product );
			},
			10,
			2
		);
	}

	/**
	 * @param array $value_options
	 *
	 * @return array
	 */
	protected function add_value_option( array $value_options ): array {
		$value_options[ self::VALUE_KEY ] = 'From WooCommerce Brands';

		return $value_options;
	}

	/**
	 * @param mixed      $value
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	protected function get_brand( $value, WC_Product $product ) {
		if ( self::VALUE_KEY === $value ) {
			$product_id = $product instanceof WC_Product_Variation ? $product->get_parent_id() : $product->get_id();

			$terms = get_the_terms( $product_id, 'product_brand' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms[0] ) && ! empty( $terms[0]->name ) ) {
				// set the first selected brand as primary brand
				$value = $terms[0]->name;
			}
		}

		return ! empty( $value ) ? $value : null;
	}
}
