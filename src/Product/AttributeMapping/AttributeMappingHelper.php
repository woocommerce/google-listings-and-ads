<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Material;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Multipack;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Pattern;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Size;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\WithMappingInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Helper Class for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping
 */
class AttributeMappingHelper implements Service {


	private const ATTRIBUTES_AVAILABLE_FOR_MAPPING = [
		Adult::class,
		AgeGroup::class,
		Brand::class,
		Color::class,
		Condition::class,
		Gender::class,
		GTIN::class,
		IsBundle::class,
		Material::class,
		MPN::class,
		Multipack::class,
		Pattern::class,
		Size::class,
		SizeSystem::class,
		SizeType::class,
	];

	public const CATEGORY_CONDITION_TYPE_ALL    = 'ALL';
	public const CATEGORY_CONDITION_TYPE_ONLY   = 'ONLY';
	public const CATEGORY_CONDITION_TYPE_EXCEPT = 'EXCEPT';

	/**
	 * Gets all the available attributes for mapping
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		$destinations = [];

		/**
		 * @var WithMappingInterface $attribute
		 */
		foreach ( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING as $attribute ) {
			array_push(
				$destinations,
				[
					'id'    => $attribute::get_id(),
					'label' => $attribute::get_name(),
					'enum'  => $attribute::is_enum(),
				]
			);
		}

		return $destinations;
	}

	/**
	 * Gets all the available sources identified by attribute key
	 *
	 * @return array
	 */
	public function get_sources(): array {
		$sources = [];

		/**
		 * @var AttributeInterface $attribute
		 */
		foreach ( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING as $attribute ) {

			$attribute_sources = [];

			foreach ( $attribute::get_sources() as $key => $value ) {
				array_push(
					$attribute_sources,
					[
						'id'    => $key,
						'label' => $value,
					]
				);
			}

			$sources[ $attribute::get_id() ] = $attribute_sources;
		}

		return $sources;
	}

	/**
	 * Gets the taxonomies and global attributes to render them as options in the frontend.
	 *
	 * @return array An array with the taxonomies and global attributes
	 */
	public static function get_source_taxonomies(): array {
		$object_taxonomies = get_object_taxonomies( 'product', 'objects' );
		$taxonomies        = [];
		$attributes        = [];
		$sources           = [];

		foreach ( $object_taxonomies as $taxonomy ) {
			if ( taxonomy_is_product_attribute( $taxonomy->name ) ) {
				$attributes[ 'taxonomy:' . $taxonomy->name ] = $taxonomy->label;
				continue;
			}

			$taxonomies[ 'taxonomy:' . $taxonomy->name ] = $taxonomy->label;
		}

		asort( $taxonomies );
		asort( $attributes );

		$attributes = apply_filters( 'woocommerce_gla_attribute_mapping_sources_global_attributes', $attributes );
		$taxonomies = apply_filters( 'woocommerce_gla_attribute_mapping_sources_taxonomies', $taxonomies );

		if ( ! empty( $attributes ) ) {
			$sources = array_merge(
				[
					'disabled:attributes' => __( '- Global attributes -', 'google-listings-and-ads' ),
				],
				$attributes
			);
		}

		if ( ! empty( $taxonomies ) ) {
			$sources = array_merge(
				$sources,
				[
					'disabled:taxonomies' => __( '- Taxonomies -', 'google-listings-and-ads' ),
				],
				$taxonomies
			);
		}

		return $sources;
	}

	/**
	 * Get a list of the available product sources.
	 *
	 * @return array An array with the available product sources.
	 */
	public static function get_source_product_fields() {
		$fields = [
			'product:backorders'       => __( 'Allow backorders setting', 'google-listings-and-ads' ),
			'product:title'            => __( 'Product title', 'google-listings-and-ads' ),
			'product:sku'              => __( 'SKU', 'google-listings-and-ads' ),
			'product:stock_quantity'   => __( 'Stock Qty', 'google-listings-and-ads' ),
			'product:stock_status'     => __( 'Stock Status', 'google-listings-and-ads' ),
			'product:tax_class'        => __( 'Tax class', 'google-listings-and-ads' ),
			'product:variation_title'  => __( 'Variation title (Product title for non variable Products)', 'google-listings-and-ads' ),
			'product:weight'           => __( 'Weight (raw value, no units)', 'google-listings-and-ads' ),
			'product:weight_with_unit' => __( 'Weight (with units)', 'google-listings-and-ads' ),
		];
		asort( $fields );

		$fields = array_merge(
			[
				'disabled:product' => __( '- Product fields -', 'google-listings-and-ads' ),
			],
			$fields
		);

		return apply_filters( 'woocommerce_gla_attribute_mapping_sources_product_fields', $fields );
	}

	/**
	 * Allowing to register custom attributes by using a filter.
	 *
	 * @return array The custom attributes
	 */
	public static function get_source_custom_attributes() {
		$attributes     = [];
		$attribute_keys = apply_filters( 'woocommerce_gla_attribute_mapping_sources_custom_attributes', [] );
		foreach ( $attribute_keys as $key ) {
			$attributes[ 'attribute:' . $key ] = $key;
		}

		if ( ! empty( $attributes ) ) {
			$attributes = array_merge(
				[
					'disabled:attribute' => __( '- Custom Attributes -', 'google-listings-and-ads' ),
				],
				$attributes
			);
		}

		return $attributes;
	}

	/**
	 * Get the available conditions for the category.
	 *
	 * @return string[] The list of available category conditions
	 */
	public function get_category_condition_types(): array {
		return [
			self::CATEGORY_CONDITION_TYPE_ALL,
			self::CATEGORY_CONDITION_TYPE_EXCEPT,
			self::CATEGORY_CONDITION_TYPE_ONLY,
		];
	}

	public static function get_product_taxonomy( $taxonomy, WC_Product $product ): string {
		$values = get_the_terms( $product->get_id() , $taxonomy);
		return implode('|', wp_list_pluck( $values, 'name' ) );
	}

	public static function get_product_attribute( $attribute, WC_Product $product ): string {
		return $product->get_attribute( $attribute );
	}

	public static function get_product_field( $field, WC_Product $product): ?string {
		if ( 'variation_title' === $field ) {
			$product->get_title();
		}

		if ( 'weight_with_unit' === $field ) {
			$weight = $product->get_weight();
			return $weight . ' ' . get_option( 'woocommerce_weight_unit' );
		}

		if ( is_callable( [ $product, 'get_' . $field ] ) ) {
			$getter = 'get_' . $field;

			return $product->$getter();
		}

		return null;
	}

}
