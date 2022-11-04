<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait for fields
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
				self::get_source_product_fields(),
				self::get_source_taxonomies(),
				self::get_source_custom_attributes()
			),
			self::get_id()
		);
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
	public static function get_source_product_fields(): array {
		$fields = [
			'product:backorders'       => __( 'Allow backorders setting', 'google-listings-and-ads' ),
			'product:title'            => __( 'Product title', 'google-listings-and-ads' ),
			'product:sku'              => __( 'SKU', 'google-listings-and-ads' ),
			'product:stock_quantity'   => __( 'Stock Qty', 'google-listings-and-ads' ),
			'product:stock_status'     => __( 'Stock Status', 'google-listings-and-ads' ),
			'product:tax_class'        => __( 'Tax class', 'google-listings-and-ads' ),
			'product:name'             => __( 'Variation title', 'google-listings-and-ads' ),
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
	public static function get_source_custom_attributes(): array {
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
}
