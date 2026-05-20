<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Wires Google for WooCommerce abilities into WooCommerce's ability loader.
 */
class Abilities implements Registerable, Service {

	/**
	 * Register the ability definition filter.
	 */
	public function register(): void {
		if ( ! interface_exists( AbilityDefinition::class ) ) {
			return;
		}

		add_filter( 'woocommerce_ability_definition_classes', [ $this, 'add_ability_definition_classes' ] );
	}

	/**
	 * Add Google for WooCommerce ability definitions.
	 *
	 * @param array $classes Ability definition classes.
	 * @return array
	 */
	public function add_ability_definition_classes( array $classes ): array {
		$classes[] = GetSetupStatus::class;

		return $classes;
	}
}
