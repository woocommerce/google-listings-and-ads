<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ServiceBasedMerchantState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ServiceBasedMerchant
 */
class ServiceBasedMerchantState implements Service, Registerable, OptionsAwareInterface, TransientsAwareInterface {

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
	 * Register hooks to clear cache when products are updated.
	 */
	public function register(): void {
		// Clear cache when products are added or updated.
		// Note: woocommerce_update_product passes (int $product_id, WC_Product $product)
		add_action( 'woocommerce_new_product', [ $this, 'clear_cache_on_product_change' ], 10 );
		add_action( 'woocommerce_update_product', [ $this, 'clear_cache_on_product_change' ], 10, 2 );
		add_action( 'woocommerce_new_product_variation', [ $this, 'clear_cache_on_product_change' ], 10 );
		add_action( 'woocommerce_update_product_variation', [ $this, 'clear_cache_on_product_change' ], 10, 2 );

		// Clear cache when products are deleted or trashed.
		add_action( 'wp_trash_post', [ $this, 'maybe_clear_cache_on_post_change' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'maybe_clear_cache_on_post_change' ], 10 );
		add_action( 'deleted_post', [ $this, 'maybe_clear_cache_on_post_change' ], 10 );

		// Clear cache when products are restored from trash.
		add_action( 'untrashed_post', [ $this, 'maybe_clear_cache_on_post_change' ], 10, 2 );

		// Clear cache when WooCommerce clears product transients.
		add_action( 'woocommerce_delete_product_transients', [ $this, 'clear_cache_on_product_change' ], 10 );
	}

	/**
	 * Clear cache when a product is changed.
	 *
	 * @param int|WC_Product  $product_id_or_object Product ID or product object.
	 * @param WC_Product|null $product Optional product object (passed by woocommerce_update_product hook).
	 */
	public function clear_cache_on_product_change( $product_id_or_object, $product = null ): void {
		// Handle both single parameter (product_id) and two parameters (product_id, product) from hooks
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		} elseif ( is_numeric( $product_id_or_object ) ) {
			$product_id = $product_id_or_object;
		} else {
			$product_id = $product_id_or_object->get_id();
		}

		$this->clear_physical_products_cache();
		// Delete the option so it gets recalculated on next check, or recalculate now
		$this->options->delete( OptionsInterface::IS_SERVICE_BASED_MERCHANT );
		$this->calculate_service_based_merchant();
	}

	/**
	 * Clear cache when a post is changed, but only if it's a product.
	 *
	 * @param int    $post_id         Post ID.
	 * @param string $previous_status Optional previous status (passed by wp_trash_post/untrashed_post hooks).
	 */
	public function maybe_clear_cache_on_post_change( int $post_id, $previous_status = null ): void {
		$post_type = get_post_type( $post_id );
		if ( 'product' === $post_type ) {
			$this->clear_physical_products_cache();
			// Delete the option so it gets recalculated on next check, or recalculate now
			$this->options->delete( OptionsInterface::IS_SERVICE_BASED_MERCHANT );
			$this->calculate_service_based_merchant();
		}
	}

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
		// Clear the cache before calculating to ensure fresh data.
		$this->clear_physical_products_cache();

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
	 * Clear the cached result of has_physical_products.
	 *
	 * This should be called when products are added, updated, or deleted
	 * to ensure the cache stays accurate.
	 *
	 * @return bool True if the cache was cleared, false otherwise.
	 */
	public function clear_physical_products_cache(): bool {
		return $this->transients->delete( TransientsInterface::HAS_PHYSICAL_PRODUCTS );
	}

	/**
	 * Reset the service-based merchant status by clearing both the option and cache.
	 *
	 * This forces a recalculation on the next call to is_service_based_merchant().
	 *
	 * @return void
	 */
	public function reset_service_based_merchant_status(): void {
		$this->options->delete( OptionsInterface::IS_SERVICE_BASED_MERCHANT );
		$this->clear_physical_products_cache();
	}
}
