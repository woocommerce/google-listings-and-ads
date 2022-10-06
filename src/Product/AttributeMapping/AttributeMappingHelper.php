<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
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
	 * @var Transients|null
	 */
	private static ?Transients $transients = null;

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
	 * Returns a single instance of Transients
	 *
	 * @return Transients A single instance of Transients
	 */
	public static function get_transients_object(): Transients {
		if ( is_null( self::$transients ) ) {
			self::$transients = new Transients();
		}

		return self::$transients;
	}
	/**
	 * Gets the taxonomies and global attributes to render them as options in the frontend.
	 *
	 * @return array An array with the taxonomies and global attributes
	 */
	public static function get_source_taxonomies(): array {
		$taxonomies        = get_object_taxonomies( 'product', 'objects' );
		$parsed_taxonomies = [];
		$attributes        = [];
		$sources           = [];

		foreach ( $taxonomies as $taxonomy ) {
			if ( taxonomy_is_product_attribute( $taxonomy->name ) ) {
				$attributes[ 'taxonomy:' . $taxonomy->name ] = $taxonomy->label;
				continue;
			}

			$parsed_taxonomies[ 'taxonomy:' . $taxonomy->name ] = $taxonomy->label;
		}

		asort( $parsed_taxonomies );
		asort( $attributes );

		$attributes        = apply_filters( 'woocommerce_gla_attribute_mapping_fields_global_attributes', $attributes );
		$parsed_taxonomies = apply_filters( 'woocommerce_gla_attribute_mapping_fields_taxonomies', $parsed_taxonomies );

		if ( ! empty( $attributes ) ) {
			$sources = array_merge(
				[
					'disabled:attributes' => __( '- Global attributes -', 'google-listings-and-ads' ),
				],
				$attributes
			);
		}

		if ( ! empty( $parsed_taxonomies ) ) {
			$sources = array_merge(
				$sources,
				[
					'disabled:taxonomies' => __( '- Taxonomies -', 'google-listings-and-ads' ),
				],
				$parsed_taxonomies
			);
		}

		return $sources;
	}

	/**
	 * Get a list of the available product fields.
	 *
	 * @return array An array with the available product fields.
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

		return apply_filters( 'woocommerce_gla_attribute_mapping_fields_product_fields', $fields );
	}

	/**
	 * Returns the available meta (non system) metas
	 *
	 * @return array The available metas
	 */
	public static function get_source_custom_fields() {
		global $wpdb;
		$transients = self::get_transients_object();

		$cached_metas = $transients->get(
			TransientsInterface::ATTRIBUTE_MAPPING_META_FIELDS,
		);

		if ( $cached_metas ) {
			return $cached_metas;
		}

		$sources = [];

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"SELECT DISTINCT {$wpdb->postmeta}.meta_key
		                FROM {$wpdb->posts}
			       LEFT JOIN {$wpdb->postmeta}
			              ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key NOT LIKE %s
				       WHERE {$wpdb->posts}.post_type IN ( %s, %s )",
			[ $wpdb->esc_like( '_' ) . '%', 'product_variation', 'product' ]
		);

		$meta_keys = $wpdb->get_col( $sql );
		foreach ( $meta_keys as $meta_key ) {
			$sources[ 'meta:' . $meta_key ] = $meta_key;
		}

		if ( ! empty( $sources ) ) {
			$sources = array_merge(
				[
					'disabled:meta' => __( '- Custom fields -', 'google-listings-and-ads' ),
				],
				$sources
			);
		}

		$transients->set(
			TransientsInterface::ATTRIBUTE_MAPPING_META_FIELDS,
			$sources,
			HOUR_IN_SECONDS
		);

		return apply_filters( 'woocommerce_gla_attribute_mapping_fields_custom_fields', $sources );
	}

	/**
	 * Allowing to register custom attributes by using a filter.
	 *
	 * @return array The custom attributes
	 */
	public static function get_source_custom_attributes() {
		$attributes     = [];
		$attribute_keys = apply_filters( 'woocommerce_gla_attribute_mapping_fields_custom_attributes', [] );
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

	/**
	 * Returns all the available fields
	 *
	 * @param string $attribute_id The attribute ID to get the fields for
	 * @return array The available fields
	 */
	public static function get_fields( $attribute_id ): array {
		return apply_filters(
			'woocommerce_gla_attribute_mapping_fields',
			array_merge(
				self::get_source_product_fields(),
				self::get_source_taxonomies(),
				self::get_source_custom_fields(),
				self::get_source_custom_attributes()
			),
			$attribute_id
		);
	}
}
