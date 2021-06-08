<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidClass;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class AttributeManager
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 */
class AttributeManager implements Service {

	use PluginHelper;
	use ValidateInterface;

	public const ATTRIBUTES = [
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
		Adult::class,
	];

	/**
	 * @var array Attribute types mapped to product types
	 */
	protected $attribute_types_map;

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

		update_post_meta( $product->get_id(), $this->prefix_meta_key( $attribute::get_id() ), $value );
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

		$attribute_class = $this->get_attribute_types_for_product( $product )[ $attribute_id ];
		$value           = get_post_meta( $product->get_id(), $this->prefix_meta_key( $attribute_id ), true );

		if ( null === $value || '' === $value ) {
			return null;
		}

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

		delete_post_meta( $product->get_id(), $this->prefix_meta_key( $attribute_id ) );
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
	 * Returns an array of attribute types for all product types
	 *
	 * @return string[][] of attribute classes mapped to product types
	 */
	public function get_attribute_types_map(): array {
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
				'gla_error',
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
		$available_attributes = apply_filters( 'gla_product_attribute_types', self::ATTRIBUTES );

		$this->attribute_types_map = [];
		foreach ( $available_attributes as $attribute_type ) {
			$this->validate_interface( $attribute_type, AttributeInterface::class );

			$attribute_id     = call_user_func( [ $attribute_type, 'get_id' ] );
			$applicable_types = call_user_func( [ $attribute_type, 'get_applicable_product_types' ] );

			/**
			 * Filters the list of applicable product types for each attribute.
			 *
			 * @param string[] $applicable_types Array of WooCommerce product types
			 * @param string   $attribute_type   Attribute class name (FQN)
			 */
			$applicable_types = apply_filters( "gla_attribute_applicable_product_types_{$attribute_id}", $applicable_types, $attribute_type );

			foreach ( $applicable_types as $product_type ) {
				$this->attribute_types_map[ $product_type ]                  = $this->attribute_types_map[ $product_type ] ?? [];
				$this->attribute_types_map[ $product_type ][ $attribute_id ] = $attribute_type;
			}
		}
	}
}
