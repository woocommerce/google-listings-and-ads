<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Helper Class for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class AttributeMappingHelper implements Service {


	private const ATTRIBUTES_AVAILABLE_FOR_MAPPING = [
		Adult::class,
		AgeGroup::class,
		Color::class,
	];

	/**
	 * Gets all the available destinations
	 *
	 * @return array
	 */
	public function get_destinations(): array {
		$destinations = [];

		foreach ( self::ATTRIBUTES_AVAILABLE_FOR_MAPPING as $attribute ) {
			$destinations[ $attribute::get_id() ] = $attribute::get_name();
		}

		return $destinations;
	}

	/**
	 * Gets all the available sources identified by destination key
	 *
	 * @return array
	 */
	public function get_sources(): array {
		$sources = [];

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
		$taxonomies = get_object_taxonomies( 'product' );
		$taxes      = [];
		$attributes = [];
		foreach ( $taxonomies as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			if ( taxonomy_is_product_attribute( $taxonomy ) ) {
				$attributes[ 'tax:' . $taxonomy ] = $tax->labels->name;
				continue;
			}

			$taxes[ 'tax:' . $taxonomy ] = $tax->labels->name;
		}
		asort( $taxes );
		asort( $attributes );

		return array_merge(
			[
				'disabled:attributes' => __( '- Global attributes -', 'google-listings-and-ads' ),
			],
			$attributes,
			[
				'disabled:taxes' => __( '- Taxonomies -', 'google-listings-and-ads' ),
			],
			$taxes
		);
	}
}
