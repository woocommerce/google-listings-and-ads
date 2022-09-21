<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Helper Class for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class AttributeMappingHelper implements Service {

	/**
	 * @var AttributeMappingRulesQuery $rules_query
	 */
	private AttributeMappingRulesQuery $rules_query;


	private const ATTRIBUTES_AVAILABLE_FOR_MAPPING = [
		Adult::class,
		AgeGroup::class,
		Color::class,
	];

	public const CATEGORY_CONDITION_TYPE_ALL    = 'ALL';
	public const CATEGORY_CONDITION_TYPE_ONLY   = 'ONLY';
	public const CATEGORY_CONDITION_TYPE_EXCEPT = 'EXCEPT';


	/**
	 * @param AttributeMappingRulesQuery $rules_query
	 */
	public function __construct( AttributeMappingRulesQuery $rules_query ) {
		$this->rules_query = $rules_query;
	}

	/**
	 * Gets all the available attributes for mapping
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		$destinations = [];

		/**
		 * @var AttributeInterface $attribute
		 */
		foreach ( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING as $attribute ) {
			$destinations[ $attribute::get_id() ] = $attribute::get_name();
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
			$sources[ $attribute::get_id() ] = $attribute::get_sources();
		}

		return $sources;
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
	 * Gets all the rules from Database
	 *
	 * @return array The rules from database
	 */
	public function get_rules(): array {
		return $this->rules_query->get_results();
	}

	/**
	 * Gets a specific rule from Database
	 *
	 * @param int $rule_id The rule ID to get from Database
	 * @return array The rules from database
	 */
	public function get_rule( int $rule_id ): array {
		return $this->rules_query->where( 'id', $rule_id )->get_row();
	}

	/**
	 * Insert a rule in database
	 *
	 * @param array $rule The rule to insert
	 * @return array|null The inserted or updated rule or null if the update/insert failed
	 */
	public function insert_rule( array $rule ): ?array {
		return $this->rules_query->insert( $rule ) ? $this->get_rule( $this->rules_query->last_insert_id() ) : null;
	}

	/**
	 * Update a rule in database
	 *
	 * @param array $rule The rule to update
	 * @return array|null The inserted or updated rule or null if the update/insert failed
	 */
	public function update_rule( array $rule ): ?array {
		return $this->rules_query->update( $rule, [ 'id' => $rule['id'] ] ) ? $this->get_rule( $rule['id'] ) : null;
	}

	/**
	 * Removes a rule from DB
	 *
	 * @param int $rule_id The Rule ID to remove
	 *
	 * @return bool True if the deletion was successful
	 */
	public function delete_rule( int $rule_id ): bool {
		return (bool) $this->rules_query->delete( 'id', $rule_id );
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
