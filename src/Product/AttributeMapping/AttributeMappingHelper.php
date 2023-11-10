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
	public static function get_attribute_by_id( string $attribute_id ): ?string {
		foreach ( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING as $class ) {
			if ( $class::get_id() === $attribute_id ) {
				return $class;
			}
		}

		return null;
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
		$attribute         = self::get_attribute_by_id( $attribute_id );
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
