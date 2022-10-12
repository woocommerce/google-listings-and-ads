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
	 * Get the attribute class based on attribute ID.
	 *
	 * @param string $attribute_id  The attribute ID to get the class
	 * @return string|null The attribute class path or null if it's not found
	 */
	private function get_attribute_by_id( string $attribute_id ): ?string {

		// Find the attribute class by id using a filter since PHP doesn't support user defined functions for comparison.
		// array_values is there because array_filter preserves keys index
		$attribute = array_values( array_filter( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING, function ( $class ) use ( $attribute_id ) {
			/**
			 * @var AttributeInterface $class
			 */
			return $class::get_id() === $attribute_id;
		}));

		return ! empty( $attribute ) ? $attribute[0] : null;
	}

	/**
	 * Get the sources for an attribute
	 *
	 * @param string $attribute_id The attribute ID to get the sources from.
	 * @return array The sources for the attribute
	 */
	public function get_sources_for_attribute( string $attribute_id ): array {

		/**
		 * @var AttributeInterface $attribute
		 */
		$attribute = self::get_attribute_by_id( $attribute_id );
		$attribute_sources = [];

		if ( is_null( $attribute ) ) {
			return $attribute_sources;
		}

		foreach ( $attribute::get_sources() as $key => $value ) {
			array_push(
				$attribute_sources,
				[
					'id'    => $key,
					'label' => $value,
				]
			);
		}

		return $attribute_sources;

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
			'product:product_title'    => __( 'Product title', 'google-listings-and-ads' ),
			'product:sku'              => __( 'SKU', 'google-listings-and-ads' ),
			'product:stock_qty'        => __( 'Stock Qty', 'google-listings-and-ads' ),
			'product:stock_status'     => __( 'Stock Status', 'google-listings-and-ads' ),
			'product:tax_class'        => __( 'Tax class', 'google-listings-and-ads' ),
			'product:variation_title'  => __( 'Variation title', 'google-listings-and-ads' ),
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

}
