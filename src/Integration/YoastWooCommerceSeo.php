<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class YoastWooCommerceSeo
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class YoastWooCommerceSeo implements IntegrationInterface {

	protected const VALUE_KEY = 'yoast_seo';

	/**
	 * @var array Meta values stored by Yoast WooCommerce SEO plugin.
	 */
	protected $yoast_global_identifiers;

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WPSEO_WOO_VERSION' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter(
			'gla_product_attribute_value_options_mpn',
			function ( array $value_options ) {
				return $this->add_value_option( $value_options );
			}
		);
		add_filter(
			'gla_product_attribute_value_options_gtin',
			function ( array $value_options ) {
				return $this->add_value_option( $value_options );
			}
		);
		add_filter(
			'gla_product_attribute_value_mpn',
			function ( $value, WC_Product $product ) {
				return $this->get_mpn( $value, $product );
			},
			10,
			2
		);
		add_filter(
			'gla_product_attribute_value_gtin',
			function ( $value, WC_Product $product ) {
				return $this->get_gtin( $value, $product );
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
		$value_options[ self::VALUE_KEY ] = 'From Yoast WooCommerce SEO';

		return $value_options;
	}

	/**
	 * @param mixed      $value
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	protected function get_mpn( $value, WC_Product $product ) {
		if ( self::VALUE_KEY === $value ) {
			$value = $this->get_identifier_value( 'mpn', $product );
		}

		return ! empty( $value ) ? $value : null;
	}

	/**
	 * @param mixed      $value
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	protected function get_gtin( $value, WC_Product $product ) {
		if ( self::VALUE_KEY === $value ) {
			$gtin_values = [
				$this->get_identifier_value( 'isbn', $product ),
				$this->get_identifier_value( 'gtin8', $product ),
				$this->get_identifier_value( 'gtin12', $product ),
				$this->get_identifier_value( 'gtin13', $product ),
				$this->get_identifier_value( 'gtin14', $product ),
			];
			$gtin_values = array_values( array_filter( $gtin_values ) );

			$value = $gtin_values[0] ?? null;
		}

		return $value;
	}

	/**
	 * @param string     $key
	 * @param WC_Product $product
	 *
	 * @return mixed|null
	 */
	protected function get_identifier_value( string $key, WC_Product $product ) {
		if ( ! isset( $this->yoast_global_identifiers ) ) {
			$product_id = $product instanceof WC_Product_Variation ? $product->get_parent_id() : $product->get_id();

			$this->yoast_global_identifiers = get_post_meta( $product_id, 'wpseo_global_identifier_values', true );
		}

		return ! empty( $this->yoast_global_identifiers[ $key ] ) ? $this->yoast_global_identifiers[ $key ] : null;
	}
}
