<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
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
	 * @var array Attribute types mapped to product types
	 */
	protected $attribute_types_map;

	/**
	 * @var AttributeMappingRulesQuery
	 */
	protected $attribute_mapping_rules_query;

	/**
	 * @var WC
	 */
	protected $wc;

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
	 * Return all attribute values for the given product, after the mapping rules, GLA attributes, and filters have been applied.
	 * GLA Attributes has priority over the product attributes.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Product $product
	 *
	 * @return array of attribute values
	 * @throws InvalidValue When the product does not exist.
	 */
	public function get_all_aggregated_values( WC_Product $product ) {
		$attributes = $this->get_all_values( $product );

		$parent_product = null;
		// merge with parent's attributes if it's a variation product
		if ( $product instanceof WC_Product_Variation ) {
			$parent_product    = $this->wc->get_product( $product->get_parent_id() );
			$parent_attributes = $this->get_all_values( $parent_product );
			$attributes        = array_merge( $parent_attributes, $attributes );
		}

		$mapping_rules = $this->attribute_mapping_rules_query->get_results();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $product,
				'parent_wc_product' => $parent_product,
				'targetCountry'     => 'US', // targetCountry is required to create a new WCProductAdapter instance, but it's not used in the attributes context.
				'gla_attributes'    => $attributes,
				'mapping_rules'     => $mapping_rules,
			]
		);

		foreach ( self::ATTRIBUTES as $attribute_class ) {
			$attribute_id = $attribute_class::get_id();
			if ( $attribute_id === 'size' ) {
				$attribute_id = 'sizes';
			}

			if ( isset( $adapted_product->$attribute_id ) ) {
				$attributes[ $attribute_id ] = $adapted_product->$attribute_id;
			}
		}

		return $attributes;
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
