<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
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
	 * @var array Meta values stored by Yoast WooCommerce SEO plugin (per product).
	 */
	protected $yoast_global_identifiers = [];

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
			'woocommerce_gla_product_attribute_value_options_mpn',
			function ( array $value_options ) {
				return $this->add_value_option( $value_options );
			}
		);
		add_filter(
			'woocommerce_gla_product_attribute_value_options_gtin',
			function ( array $value_options ) {
				return $this->add_value_option( $value_options );
			}
		);
		add_filter(
			'woocommerce_gla_product_attribute_value_mpn',
			function ( $value, WC_Product $product ) {
				return $this->get_mpn( $value, $product );
			},
			10,
			2
		);
		add_filter(
			'woocommerce_gla_product_attribute_value_gtin',
			function ( $value, WC_Product $product ) {
				return $this->get_gtin( $value, $product );
			},
			10,
			2
		);

		add_filter(
			'woocommerce_gla_attribute_mapping_sources',
			function ( $sources, $attribute_id ) {
				return $this->load_yoast_seo_attribute_mapping_sources( $sources, $attribute_id );
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
		if ( strpos( $value, self::VALUE_KEY ) === 0 ) {
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
		if ( strpos( $value, self::VALUE_KEY ) === 0 ) {
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
	 * Get the identifier value from cache or product meta.
	 *
	 * @param string     $key
	 * @param WC_Product $product
	 *
	 * @return mixed|null
	 */
	protected function get_identifier_value( string $key, WC_Product $product ) {
		$product_id = $product->get_id();

		if ( ! isset( $this->yoast_global_identifiers[ $product_id ] ) ) {
			$this->yoast_global_identifiers[ $product_id ] = $this->get_identifier_meta( $product );
		}

		return ! empty( $this->yoast_global_identifiers[ $product_id ][ $key ] ) ? $this->yoast_global_identifiers[ $product_id ][ $key ] : null;
	}

	/**
	 * Get identifier meta from product.
	 * For variations fallback to parent product if meta is empty.
	 *
	 * @since 2.3.1
	 *
	 * @param WC_Product $product
	 *
	 * @return mixed|null
	 */
	protected function get_identifier_meta( WC_Product $product ) {
		if ( ! $product ) {
			return null;
		}

		if ( $product instanceof WC_Product_Variation ) {
			$identifiers = $product->get_meta( 'wpseo_variation_global_identifiers_values', true );

			if ( ! is_array( $identifiers ) || empty( array_filter( $identifiers ) ) ) {
				$parent_product = wc_get_product( $product->get_parent_id() );
				$identifiers    = $this->get_identifier_meta( $parent_product );
			}

			return $identifiers;
		}

		return $product->get_meta( 'wpseo_global_identifier_values', true );
	}

	/**
	 *
	 * Merge the YOAST Fields with the Attribute Mapping available sources
	 *
	 * @param array  $sources The current sources
	 * @param string $attribute_id The Attribute ID
	 * @return array The merged sources
	 */
	protected function load_yoast_seo_attribute_mapping_sources( array $sources, string $attribute_id ): array {
		if ( $attribute_id === GTIN::get_id() ) {
			return array_merge( self::get_yoast_seo_attribute_mapping_gtin_sources(), $sources );
		}

		if ( $attribute_id === MPN::get_id() ) {
			return array_merge( self::get_yoast_seo_attribute_mapping_mpn_sources(), $sources );
		}

		return $sources;
	}

	/**
	 * Load the group disabled option for Attribute mapping YOAST SEO
	 *
	 * @return array The disabled group option
	 */
	protected function get_yoast_seo_attribute_mapping_group_source(): array {
		return [ 'disabled:' . self::VALUE_KEY => __( '- Yoast SEO -', 'google-listings-and-ads' ) ];
	}

	/**
	 * Load the GTIN Fields for Attribute mapping YOAST SEO
	 *
	 * @return array The GTIN sources
	 */
	protected function get_yoast_seo_attribute_mapping_gtin_sources(): array {
		return array_merge( self::get_yoast_seo_attribute_mapping_group_source(), [ self::VALUE_KEY . ':gtin' => __( 'GTIN Field', 'google-listings-and-ads' ) ] );
	}

	/**
	 * Load the MPN Fields for Attribute mapping YOAST SEO
	 *
	 * @return array The MPN sources
	 */
	protected function get_yoast_seo_attribute_mapping_mpn_sources(): array {
		return array_merge( self::get_yoast_seo_attribute_mapping_group_source(), [ self::VALUE_KEY . ':mpn' => __( 'MPN Field', 'google-listings-and-ads' ) ] );
	}
}
