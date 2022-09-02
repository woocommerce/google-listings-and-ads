<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;

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
}
