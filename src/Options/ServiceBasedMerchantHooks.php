<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class ServiceBasedMerchantHooks
 *
 * Listens for product changes and resets the cached service-based merchant
 * flag so that it is recalculated on the next page load. This covers both
 * directions: service-based → product-based (physical product added) and
 * product-based → service-based (all physical products removed).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class ServiceBasedMerchantHooks implements Service, Registerable {

	/**
	 * @var ServiceBasedMerchantState
	 */
	private ServiceBasedMerchantState $service_based_merchant_state;

	/**
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Register hooks for product lifecycle events that may change the
	 * service-based merchant classification.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_product', [ $this, 'handle_product_change' ], 10, 2 );
		add_action( 'woocommerce_update_product', [ $this, 'handle_product_change' ], 10, 2 );
		add_action( 'untrashed_post', [ $this, 'handle_product_restore' ] );
		add_action( 'trashed_post', [ $this, 'handle_product_removal' ] );
		add_action( 'deleted_post', [ $this, 'handle_product_removal' ] );
	}

	/**
	 * When a physical product is created or updated and the store is currently
	 * classified as service-based, reset the flag so it is recalculated.
	 *
	 * @param int              $product_id Product ID.
	 * @param \WC_Product|null $product    Product object (passed by WC on update, may be null on new).
	 */
	public function handle_product_change( int $product_id, $product = null ): void {
		if ( ! $this->service_based_merchant_state->is_service_based_merchant() ) {
			return;
		}

		if ( null === $product ) {
			$product = wc_get_product( $product_id );
		}

		if ( $product && $product->needs_shipping() ) {
			$this->service_based_merchant_state->reset_service_based_merchant_status();
		}
	}

	/**
	 * When a product is restored from the trash and the store is currently
	 * classified as service-based, reset the flag so it is recalculated.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_product_restore( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		$this->handle_product_change( $post_id );
	}

	/**
	 * When a product is trashed or deleted and the store is currently
	 * classified as product-based, reset the flag so it is recalculated.
	 * The recalculation on the next page load will re-scan all remaining
	 * published products to determine the correct classification.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_product_removal( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		if ( $this->service_based_merchant_state->is_service_based_merchant() ) {
			return;
		}

		$this->service_based_merchant_state->reset_service_based_merchant_status();
	}
}
