<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ServiceBasedMerchantState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ServiceBasedMerchant
 */
class ServiceBasedMerchantState implements Service, OptionsAwareInterface, TransientsAwareInterface {

	use OptionsAwareTrait;
	use TransientsAwareTrait;

	/**
	 * Batch size for product queries.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Cache expiration time in seconds (1 hour).
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Check if the store is service-based.
	 *
	 * If the option has not been calculated yet, it will be calculated automatically
	 * based on whether the store has physical products.
	 *
	 * @return bool True if service-based, false otherwise.
	 */
	public function is_service_based_merchant(): bool {
		$option_value = $this->options->get( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// If option is null, calculate it now.
		if ( null === $option_value ) {
			return $this->calculate_service_based_merchant();
		}

		return (bool) $option_value;
	}

	/**
	 * Calculate and save whether the store is service-based.
	 *
	 * This method should be called early in the onboarding process to determine
	 * if the store has physical products that require shipping.
	 *
	 * @return bool True if service-based, false otherwise.
	 */
	public function calculate_service_based_merchant(): bool {
		$service_based = ! $this->has_physical_products();

		$this->options->update( OptionsInterface::IS_SERVICE_BASED_MERCHANT, $service_based );
		return $service_based;
	}

	/**
	 * Check if the store has at least one physical product that requires shipping.
	 *
	 * Results are cached to improve performance. The cache can be bypassed or modified
	 * using the 'woocommerce_gla_has_physical_products' filter.
	 *
	 * @return bool True if at least one physical product requiring shipping is found, false otherwise.
	 */
	public function has_physical_products(): bool {
		/**
		 * Filter to bypass or modify the cached result of has_physical_products.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|null $cached_result The cached result, or null to use default caching behavior.
		 * @return bool|null True if physical products exist, false otherwise, or null to use cache.
		 */
		$filtered_result = apply_filters( 'woocommerce_gla_has_physical_products', null );

		if ( null !== $filtered_result ) {
			return (bool) $filtered_result;
		}

		// Try to get from cache (transients can't store booleans, so we store as int: 0 or 1).
		$cached_value = $this->transients->get( TransientsInterface::HAS_PHYSICAL_PRODUCTS );

		if ( null !== $cached_value ) {
			return (bool) $cached_value;
		}

		// Calculate the value.
		$has_physical_products = $this->calculate_has_physical_products();

		// Cache the result (store as int since transients can't store booleans).
		$this->transients->set(
			TransientsInterface::HAS_PHYSICAL_PRODUCTS,
			$has_physical_products ? 1 : 0,
			self::CACHE_EXPIRATION
		);

		return $has_physical_products;
	}

	/**
	 * Calculate if the store has at least one physical product that requires shipping.
	 *
	 * This method performs the actual database query without using cache.
	 *
	 * @return bool True if at least one physical product requiring shipping is found, false otherwise.
	 */
	private function calculate_has_physical_products(): bool {
		$offset = 0;

		do {
			$products = wc_get_products(
				[
					'limit'  => self::BATCH_SIZE,
					'offset' => $offset,
					'status' => 'publish',
				]
			);

			foreach ( $products as $product ) {
				// Check if product is not virtual and requires shipping.
				// Note: needs_shipping() returns !is_virtual(), so this check is essentially: !is_virtual
				if ( ! $product->is_virtual() && $product->needs_shipping() ) {
					return true;
				}
			}

			$offset        += self::BATCH_SIZE;
			$products_count = count( $products );
		} while ( $products_count === self::BATCH_SIZE );

		return false;
	}

	/**
	 * Reset the service-based merchant status by clearing the option.
	 *
	 * This forces a recalculation on the next call to is_service_based_merchant().
	 *
	 * @return void
	 */
	public function reset_service_based_merchant_status(): void {
		$this->options->delete( OptionsInterface::IS_SERVICE_BASED_MERCHANT );
	}
}
