<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Adult;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Gender;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\IsBundle;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;

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
		Condition::class,
		Gender::class,
		IsBundle::class,
		SizeSystem::class,
		SizeType::class,
	];

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
			array_push($destinations, [
				'id' => $attribute::get_id(),
				'label' => $attribute::get_name(),
				'enum' => $attribute::is_enum()
			]);
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
}
