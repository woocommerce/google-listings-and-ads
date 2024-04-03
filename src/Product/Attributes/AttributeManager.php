<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributeManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class AttributeManager implements Service {

	use PluginHelper;
	use ValidateInterface;

	protected const ATTRIBUTES = [
		GTIN::class,
		MPN::class,
		Brand::class,
		Condition::class,
		Gender::class,
		Size::class,
		SizeSystem::class,
		SizeType::class,
		Color::class,
		Material::class,
		Pattern::class,
		AgeGroup::class,
		Multipack::class,
		IsBundle::class,
		AvailabilityDate::class,
		Adult::class,
	];

	/**
	 * @var AttributeMappingRulesQuery
	 */
	protected $attribute_mapping_rules_query;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var array Attribute types mapped to product types
	 */
	protected $attribute_types_map;

	/**
	 * AttributeManager constructor.
	 *
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 * @param WC                         $wc
	 */
	public function __construct( AttributeMappingRulesQuery $attribute_mapping_rules_query, WC $wc ) {
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
		$this->wc                            = $wc;
	}



	/**
	 * @param WC_Product         $product
	 * @param AttributeInterface $attribute
	 *
	 * @throws InvalidValue If the attribute is invalid for the given product.
	 */
	public function update( WC_Product $product, AttributeInterface $attribute ) {
		$this->validate( $product, $attribute::get_id() );

		if ( null === $attribute->get_value() || '' === $attribute->get_value() ) {
			$this->delete( $product, $attribute::get_id() );
			return;
		}

		$value = $attribute->get_value();
		if ( in_array( $attribute::get_value_type(), [ 'bool', 'boolean' ], true ) ) {
			$value = wc_bool_to_string( $value );
		}

		$product->update_meta_data( $this->prefix_meta_key( $attribute::get_id() ), $value );
		$product->save_meta_data();
	}

	/**
	 * @param WC_Product $product
	 * @param string     $attribute_id
	 *
	 * @return AttributeInterface|null
	 *
	 * @throws InvalidValue If the attribute ID is invalid for the given product.
	 */
	public function get( WC_Product $product, string $attribute_id ): ?AttributeInterface {
		$this->validate( $product, $attribute_id );

		$value = null;
		if ( $this->exists( $product, $attribute_id ) ) {
			$value = $product->get_meta( $this->prefix_meta_key( $attribute_id ), true );
		}

		if ( null === $value || '' === $value ) {
			return null;
		}

		$attribute_class = $this->get_attribute_types_for_product( $product )[ $attribute_id ];
		return new $attribute_class( $value );
	}

	/**
	 * Return attribute value.
	 *
	 * @param WC_Product $product
	 * @param string     $attribute_id
	 *
	 * @return mixed|null
	 */
	public function get_value( WC_Product $product, string $attribute_id ) {
		$attribute = $this->get( $product, $attribute_id );

		return $attribute instanceof AttributeInterface ? $attribute->get_value() : null;
	}

	/**
	 * Return all attributes for the given product
	 *
	 * @param WC_Product $product
	 *
	 * @return AttributeInterface[]
	 */
	public function get_all( WC_Product $product ): array {
		$all_attributes = [];
		foreach ( array_keys( $this->get_attribute_types_for_product( $product ) ) as $attribute_id ) {
			$attribute = $this->get( $product, $attribute_id );
			if ( null !== $attribute ) {
				$all_attributes[ $attribute_id ] = $attribute;
			}
		}

		return $all_attributes;
	}

	/**
	 * Return all attribute values for the given product
	 *
	 * @param WC_Product $product
	 * @param bool       $apply_rules Whether to apply attribute mapping rules
	 *
	 * @return array of attribute values
	 */
	public function get_all_values( WC_Product $product ): array {
		$all_attributes = [];
		foreach ( array_keys( $this->get_attribute_types_for_product( $product ) ) as $attribute_id ) {
			$attribute = $this->get_value( $product, $attribute_id );
			if ( null !== $attribute ) {
				$all_attributes[ $attribute_id ] = $attribute;
			}
		}

		return $all_attributes;
	}

	/**
	 * Return all attribute values for the given product, including the ones from the attribute mapping rules
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product
	 *
	 * @return array of attribute values
	 */
	public function get_all_aggregated_values( WC_Product $product ): array {
		$attributes = $this->get_all_values( $product );

		// merge with parent's attributes if it's a variation product
		if ( $product instanceof WC_Product_Variation ) {
			$parent_product    = $this->wc->get_product( $product->get_parent_id() );
			$parent_attributes = $this->get_all_values( $parent_product );
			$attributes        = array_merge( $parent_attributes, $attributes );
		}

		$mapping_rules        = $this->attribute_mapping_rules_query->get_results();
		$mapping_rules_values = $this->get_attribute_mapping_rules_values( $product, $mapping_rules );
		return array_merge( $attributes, $mapping_rules_values );
	}

	/**
	 * Get the values for the attribute mapping rules
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product
	 * @param array      $mapping_rules
	 *
	 * @return array
	 */
	public function get_attribute_mapping_rules_values( WC_Product $product, array $mapping_rules ) {
		$attributes = [];
		foreach ( $mapping_rules as $mapping_rule ) {
			if ( $this->rule_match_conditions( $product, $mapping_rule ) ) {
				$attribute_id                = $mapping_rule['attribute'];
				$attributes[ $attribute_id ] = $this->format_attribute(
					apply_filters(
						"woocommerce_gla_product_attribute_value_{$attribute_id}",
						$this->get_source( $product, $mapping_rule['source'] ),
						$product
					),
					$attribute_id
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get a source value for attribute mapping
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product The product to get the value from
	 * @param string     $source The source to get the value
	 * @return string The source value for this product
	 */
	protected function get_source( WC_Product $product, string $source ) {
		$source_type = null;

		$type_separator = strpos( $source, ':' );

		if ( $type_separator ) {
			$source_type  = substr( $source, 0, $type_separator );
			$source_value = substr( $source, $type_separator + 1 );
		}

		// Detect if the source_type is kind of product, taxonomy or attribute. Otherwise, we take it the full source as a static value.
		switch ( $source_type ) {
			case 'product':
				return $this->get_product_field( $product, $source_value );
			case 'taxonomy':
				return $this->get_product_taxonomy( $product, $source_value );
			case 'attribute':
				return $this->get_custom_attribute( $product, $source_value );
			default:
				return $source;
		}
	}

	/**
	 * Gets a custom attribute from a product
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product - The product to get the attribute from.
	 * @param string     $attribute_name - The attribute name to get.
	 * @return string|null The attribute value or null if no value is found
	 */
	protected function get_custom_attribute( WC_Product $product, $attribute_name ) {
		$attribute_value = $product->get_attribute( $attribute_name );

		if ( ! $attribute_value ) {
			$attribute_value = $product->get_meta( $attribute_name );
		}

		// We only support scalar values.
		if ( ! is_scalar( $attribute_value ) ) {
			return '';
		}

		$values = explode( WC_DELIMITER, (string) $attribute_value );
		$values = array_filter( array_map( 'trim', $values ) );
		return empty( $values ) ? '' : $values[0];
	}

	/**
	 * Get product source type  for attribute mapping.
	 * Those are fields belonging to the product core data. Like title, weight, SKU...
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product The product to get the value from
	 * @param string     $field The field to get
	 * @return string|null The field value (null if data is not available)
	 */
	protected function get_product_field( WC_Product $product, $field ) {
		if ( 'weight_with_unit' === $field ) {
			$weight = $product->get_weight();
			return $weight ? $weight . ' ' . get_option( 'woocommerce_weight_unit' ) : null;
		}

		if ( is_callable( [ $product, 'get_' . $field ] ) ) {
			$getter = 'get_' . $field;
			return $product->$getter();
		}

		return '';
	}

	/**
	 * Get taxonomy source type for attribute mapping
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product The product to get the taxonomy from
	 * @param string     $taxonomy The taxonomy to get
	 * @return string The taxonomy value
	 */
	protected function get_product_taxonomy( WC_Product $product, $taxonomy ) {
		if ( $product->is_type( 'variation' ) ) {
			$values = $product->get_attribute( $taxonomy );

			if ( ! $values ) { // if taxonomy is not a global attribute (ie product_tag), attempt to get is with wc_get_product_terms
				$values = $this->get_taxonomy_term_names( $product->get_id(), $taxonomy );
			}

			if ( ! $values ) { // if the value is still not available at this point, we try to get it from the parent
				$parent = $this->wc->get_product( $product->get_parent_id() );
				$values = $parent->get_attribute( $taxonomy );

				if ( ! $values ) {
					$values = $this->get_taxonomy_term_names( $parent->get_id(), $taxonomy );
				}
			}

			if ( is_string( $values ) ) {
				$values = explode( ', ', $values );
			}
		} else {
			$values = $this->get_taxonomy_term_names( $product->get_id(), $taxonomy );
		}

		if ( empty( $values ) || is_wp_error( $values ) ) {
			return '';
		}

		return $values[0];
	}

	/**
	 * Get a taxonomy term names from a product using
	 *
	 * @since x.x.x
	 *
	 * @param int    $product_id - The product ID to get the taxonomy term
	 * @param string $taxonomy - The taxonomy to get.
	 * @return string[] An array of term names.
	 */
	protected function get_taxonomy_term_names( $product_id, $taxonomy ) {
		$values = wc_get_product_terms( $product_id, $taxonomy );
		return wp_list_pluck( $values, 'name' );
	}


	/**
	 *
	 * Formats the attribute for sending it via Google API
	 *
	 * @since x.x.x
	 *
	 * @param string $value The value to format
	 * @param string $attribute_id The attribute ID for which this value belongs
	 * @return string|bool|int The attribute formatted based on theit attribute type
	 */
	protected function format_attribute( $value, $attribute_id ) {
		$attribute = AttributeMappingHelper::get_attribute_by_id( $attribute_id );

		if ( in_array( $attribute::get_value_type(), [ 'bool', 'boolean' ], true ) ) {
			return wc_string_to_bool( $value );
		}

		if ( in_array( $attribute::get_value_type(), [ 'int', 'integer' ], true ) ) {
			return (int) $value;
		}

		return $value;
	}

	/**
	 * Check if the current product match the conditions for applying the Attribute mapping rule.
	 * For now the conditions are just matching with the product category conditions.
	 *
	 * @since x.x.x
	 *
	 * @param WC_Product $product The product to check
	 * @param array      $rule The attribute mapping rule
	 * @return bool True if the rule is applicable
	 */
	protected function rule_match_conditions( WC_Product $product, array $rule ): bool {
		$attribute               = $rule['attribute'];
		$category_condition_type = $rule['category_condition_type'];

		if ( $category_condition_type === AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ALL ) {
			return true;
		}

		// size is not the real attribute, the real attribute is sizes
		if ( ! property_exists( $this, $attribute ) && $attribute !== 'size' ) {
			return false;
		}

		$base_product_id           = $product->get_parent_id() ?: $product->get_id();
		$product_category_ids      = wc_get_product_term_ids( $base_product_id, 'product_cat' );
		$categories                = explode( ',', $rule['categories'] );
		$contains_rules_categories = ! empty( array_intersect( $categories, $product_category_ids ) );

		if ( $category_condition_type === AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ONLY ) {
			return $contains_rules_categories;
		}

		return ! $contains_rules_categories;
	}

	/**
	 * @param WC_Product $product
	 * @param string     $attribute_id
	 *
	 * @throws InvalidValue If the attribute ID is invalid for the given product.
	 */
	public function delete( WC_Product $product, string $attribute_id ) {
		$this->validate( $product, $attribute_id );

		$product->delete_meta_data( $this->prefix_meta_key( $attribute_id ) );
		$product->save_meta_data();
	}

	/**
	 * Whether the attribute exists and has been set for the product.
	 *
	 * @param WC_Product $product
	 * @param string     $attribute_id
	 *
	 * @return bool
	 *
	 * @since 1.2.0
	 */
	public function exists( WC_Product $product, string $attribute_id ): bool {
		return $product->meta_exists( $this->prefix_meta_key( $attribute_id ) );
	}

	/**
	 * Returns an array of attribute types for the given product
	 *
	 * @param WC_Product $product
	 *
	 * @return string[] of attribute classes mapped to attribute IDs
	 */
	public function get_attribute_types_for_product( WC_Product $product ): array {
		return $this->get_attribute_types_for_product_types( [ $product->get_type() ] );
	}

	/**
	 * Returns an array of attribute types for the given product types
	 *
	 * @param string[] $product_types array of WooCommerce product types
	 *
	 * @return string[] of attribute classes mapped to attribute IDs
	 */
	public function get_attribute_types_for_product_types( array $product_types ): array {
		// flip the product types array to have them as array keys
		$product_types_keys = array_flip( $product_types );

		// intersect the product types with our stored attributes map to get arrays of attributes matching the given product types
		$match_attributes = array_intersect_key( $this->get_attribute_types_map(), $product_types_keys );

		// re-index the attributes map array to avoid string ($product_type) array keys
		$match_attributes = array_values( $match_attributes );

		if ( empty( $match_attributes ) ) {
			return [];
		}

		// merge all of the attribute arrays from the map (there might be duplicates) and return the results
		return array_merge( ...$match_attributes );
	}

	/**
	 * Returns all available attribute IDs.
	 *
	 * @return array
	 *
	 * @since 1.3.0
	 */
	public static function get_available_attribute_ids(): array {
		$attributes = [];
		foreach ( self::get_available_attribute_types() as $attribute_type ) {
			if ( method_exists( $attribute_type, 'get_id' ) ) {
				$attribute_id                = call_user_func( [ $attribute_type, 'get_id' ] );
				$attributes[ $attribute_id ] = $attribute_id;
			}
		}

		return $attributes;
	}

	/**
	 * Return an array of all available attribute class names.
	 *
	 * @return string[] Attribute class names
	 *
	 * @since 1.3.0
	 */
	public static function get_available_attribute_types(): array {
		/**
		 * Filters the list of available product attributes.
		 *
		 * @param string[] $attributes Array of attribute class names (FQN)
		 */
		return apply_filters( 'woocommerce_gla_product_attribute_types', self::ATTRIBUTES );
	}

	/**
	 * Returns an array of attribute types for all product types
	 *
	 * @return string[][] of attribute classes mapped to product types
	 */
	protected function get_attribute_types_map(): array {
		if ( ! isset( $this->attribute_types_map ) ) {
			$this->map_attribute_types();
		}

		return $this->attribute_types_map;
	}

	/**
	 * @param WC_Product $product
	 * @param string     $attribute_id
	 *
	 * @throws InvalidValue If the attribute type is invalid for the given product.
	 */
	protected function validate( WC_Product $product, string $attribute_id ) {
		$attribute_types = $this->get_attribute_types_for_product( $product );
		if ( ! isset( $attribute_types[ $attribute_id ] ) ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Attribute "%s" is not supported for a "%s" product (ID: %s).', $attribute_id, $product->get_type(), $product->get_id() ),
				__METHOD__
			);

			throw InvalidValue::not_in_allowed_list( 'attribute_id', array_keys( $attribute_types ) );
		}
	}

	/**
	 * @throws InvalidClass If any of the given attribute classes do not implement the AttributeInterface.
	 */
	protected function map_attribute_types(): void {
		$this->attribute_types_map = [];
		foreach ( self::get_available_attribute_types() as $attribute_type ) {
			$this->validate_interface( $attribute_type, AttributeInterface::class );

			$attribute_id     = call_user_func( [ $attribute_type, 'get_id' ] );
			$applicable_types = call_user_func( [ $attribute_type, 'get_applicable_product_types' ] );

			/**
			 * Filters the list of applicable product types for each attribute.
			 *
			 * @param string[] $applicable_types Array of WooCommerce product types
			 * @param string   $attribute_type   Attribute class name (FQN)
			 */
			$applicable_types = apply_filters( "woocommerce_gla_attribute_applicable_product_types_{$attribute_id}", $applicable_types, $attribute_type );

			foreach ( $applicable_types as $product_type ) {
				$this->attribute_types_map[ $product_type ]                  = $this->attribute_types_map[ $product_type ] ?? [];
				$this->attribute_types_map[ $product_type ][ $attribute_id ] = $attribute_type;
			}
		}
	}
}
