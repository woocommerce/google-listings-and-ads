<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Google\Service\ShoppingContent\Product as GoogleProduct;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductHelper implements Service {

	use PluginHelper;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler    $meta_handler
	 * @param WC                    $wc
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( ProductMetaHandler $meta_handler, WC $wc, MerchantCenterService $merchant_center ) {
		$this->meta_handler    = $meta_handler;
		$this->wc              = $wc;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * @param WC_Product    $product
	 * @param GoogleProduct $google_product
	 */
	public function mark_as_synced( WC_Product $product, GoogleProduct $google_product ) {
		$this->meta_handler->update_synced_at( $product, time() );
		$this->meta_handler->update_sync_status( $product, SyncStatus::SYNCED );
		$this->update_empty_visibility( $product );

		// merge and update all google product ids
		$current_google_ids = $this->meta_handler->get_google_ids( $product );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique( array_merge( $current_google_ids, [ $google_product->getTargetCountry() => $google_product->getId() ] ) );
		$this->meta_handler->update_google_ids( $product, $google_ids );

		// check if product is synced completely and remove any previous errors if it is
		$synced_countries = array_keys( $google_ids );
		$target_countries = $this->merchant_center->get_target_countries();
		if ( count( $synced_countries ) === count( $target_countries ) && empty( array_diff( $synced_countries, $target_countries ) ) ) {
			$this->meta_handler->delete_errors( $product );
			$this->meta_handler->delete_failed_sync_attempts( $product );
			$this->meta_handler->delete_sync_failed_at( $product );
		}

		// mark the parent product as synced if it's a variation
		if ( $product instanceof WC_Product_Variation ) {
			try {
				$parent_product = $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				return;
			}

			$this->mark_as_synced( $parent_product, $google_product );
		}
	}

	/**
	 * @param WC_Product $product
	 */
	public function mark_as_unsynced( WC_Product $product ) {
		$this->meta_handler->delete_synced_at( $product );
		$this->meta_handler->update_sync_status( $product, SyncStatus::NOT_SYNCED );
		$this->meta_handler->delete_google_ids( $product );
		$this->meta_handler->delete_errors( $product );
		$this->meta_handler->delete_failed_sync_attempts( $product );
		$this->meta_handler->delete_sync_failed_at( $product );

		// mark the parent product as un-synced if it's a variation
		if ( $product instanceof WC_Product_Variation ) {
			try {
				$parent_product = $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				return;
			}

			$this->mark_as_unsynced( $parent_product );
		}
	}

	/**
	 * @param WC_Product $product
	 * @param string     $google_id
	 */
	public function remove_google_id( WC_Product $product, string $google_id ) {
		$google_ids = $this->meta_handler->get_google_ids( $product );
		if ( empty( $google_ids ) ) {
			return;
		}

		$idx = array_search( $google_id, $google_ids, true );
		if ( false === $idx ) {
			return;
		}

		unset( $google_ids[ $idx ] );

		if ( ! empty( $google_ids ) ) {
			$this->meta_handler->update_google_ids( $product, $google_ids );
		} else {
			// if there are no Google IDs left then this product is no longer considered "synced"
			$this->mark_as_unsynced( $product );
		}

	}

	/**
	 * Marks a WooCommerce product as invalid and stores the errors in a meta data key.
	 *
	 * Note: If a product variation is invalid then the parent product is also marked as invalid.
	 *
	 * @param WC_Product $product
	 * @param string[]   $errors
	 */
	public function mark_as_invalid( WC_Product $product, array $errors ) {
		// bail if no errors exist
		if ( empty( $errors ) ) {
			return;
		}

		$this->meta_handler->update_errors( $product, $errors );
		$this->meta_handler->update_sync_status( $product, SyncStatus::HAS_ERRORS );
		$this->update_empty_visibility( $product );

		if ( ! empty( $errors[ GoogleProductService::INTERNAL_ERROR_REASON ] ) ) {
			// update failed sync attempts count in case of internal errors
			$failed_attempts = ! empty( $this->meta_handler->get_failed_sync_attempts( $product ) ) ?
				$this->meta_handler->get_failed_sync_attempts( $product ) :
				0;
			$this->meta_handler->update_failed_sync_attempts( $product, $failed_attempts + 1 );
			$this->meta_handler->update_sync_failed_at( $product, time() );
		}

		// mark the parent product as invalid if it's a variation
		if ( $product instanceof WC_Product_Variation ) {
			try {
				$parent_product = $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				return;
			}

			$parent_errors = ! empty( $this->meta_handler->get_errors( $parent_product ) ) ?
				$this->meta_handler->get_errors( $parent_product ) :
				[];

			$parent_errors[ $product->get_id() ] = $errors;

			$this->mark_as_invalid( $parent_product, $parent_errors );
		}
	}

	/**
	 * Marks a WooCommerce product as pending synchronization.
	 *
	 * Note: If a product variation is pending then the parent product is also marked as pending.
	 *
	 * @param WC_Product $product
	 */
	public function mark_as_pending( WC_Product $product ) {
		$this->meta_handler->update_sync_status( $product, SyncStatus::PENDING );

		// mark the parent product as pending if it's a variation
		if ( $product instanceof WC_Product_Variation ) {
			try {
				$parent_product = $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				return;
			}

			$this->mark_as_pending( $parent_product );
		}
	}

	/**
	 * Update empty (NOT EXIST) visibility meta values to SYNC_AND_SHOW.
	 *
	 * @param WC_Product $product
	 */
	protected function update_empty_visibility( WC_Product $product ): void {
		try {
			$product = $this->maybe_swap_for_parent( $product );
		} catch ( InvalidValue $exception ) {
			return;
		}

		$visibility = $this->meta_handler->get_visibility( $product );

		if ( empty( $visibility ) ) {
			$this->meta_handler->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		}
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string[]|null An array of Google product IDs stored for each WooCommerce product
	 */
	public function get_synced_google_product_ids( WC_Product $product ): ?array {
		return $this->meta_handler->get_google_ids( $product );
	}

	/**
	 * See: WCProductAdapter::map_wc_product_id()
	 *
	 * @param string $mc_product_id
	 *
	 * @return int the ID for the WC product linked to the provided Google product ID (0 if not found)
	 */
	public function get_wc_product_id( string $mc_product_id ): int {
		$pattern = '/' . preg_quote( $this->get_slug(), '/' ) . '_(\d+)$/';
		if ( ! preg_match( $pattern, $mc_product_id, $matches ) ) {
			return 0;
		}

		return intval( $matches[1] );
	}

	/**
	 * Attempt to get the WooCommerce product title.
	 * The MC ID is converted to a WC ID before retrieving the product.
	 * If we can't retrieve the title we fallback to the original MC ID.
	 *
	 * @param string $mc_product_id Merchant Center product ID.
	 *
	 * @return string
	 */
	public function get_wc_product_title( string $mc_product_id ): string {
		try {
			$product = $this->get_wc_product( $this->get_wc_product_id( $mc_product_id ) );
		} catch ( InvalidValue $e ) {
			return $mc_product_id;
		}

		return $product->get_title();
	}

	/**
	 * Get WooCommerce product
	 *
	 * @param int $product_id
	 *
	 * @return WC_Product
	 *
	 * @throws InvalidValue If the given ID doesn't reference a valid product.
	 */
	public function get_wc_product( int $product_id ): WC_Product {
		return $this->wc->get_product( $product_id );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return bool
	 */
	public function is_product_synced( WC_Product $product ): bool {
		$synced_at  = $this->meta_handler->get_synced_at( $product );
		$google_ids = $this->meta_handler->get_google_ids( $product );

		return ! empty( $synced_at ) && ! empty( $google_ids );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return bool
	 */
	public function is_sync_ready( WC_Product $product ): bool {
		$product_visibility = $product->is_visible();
		$product_status     = $product->get_status();

		if ( $product instanceof WC_Product_Variation ) {
			// Check the post status of the parent product if it's a variation
			try {
				$parent = $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				do_action(
					'woocommerce_gla_error',
					sprintf( 'Cannot sync an orphaned variation (ID: %s).', $product->get_id() ),
					__METHOD__
				);

				return false;
			}

			$product_status = $parent->get_status();

			/**
			 * Optionally hide invisible variations (disabled variations and variations with empty price).
			 *
			 * @see WC_Product_Variable::get_available_variations for filter documentation
			 */
			if ( apply_filters( 'woocommerce_hide_invisible_variations', true, $parent->get_id(), $product ) && ! $product->variation_is_visible() ) {
				$product_visibility = false;
			}
		}

		return ( ChannelVisibility::DONT_SYNC_AND_SHOW !== $this->get_channel_visibility( $product ) ) &&
			   ( in_array( $product->get_type(), ProductSyncer::get_supported_product_types(), true ) ) &&
			   ( 'publish' === $product_status ) &&
			   $product_visibility;
	}

	/**
	 * Whether the sync has failed repeatedly for the product within the given timeframe.
	 *
	 * @param WC_Product $product
	 *
	 * @return bool
	 *
	 * @see ProductSyncer::FAILURE_THRESHOLD        The number of failed attempts allowed per timeframe
	 * @see ProductSyncer::FAILURE_THRESHOLD_WINDOW The specified timeframe
	 */
	public function is_sync_failed_recently( WC_Product $product ): bool {
		$failed_attempts = $this->meta_handler->get_failed_sync_attempts( $product );
		$failed_at       = $this->meta_handler->get_sync_failed_at( $product );

		// if it has failed more times than the specified threshold AND if syncing it has failed within the specified window
		return $failed_attempts > ProductSyncer::FAILURE_THRESHOLD &&
			   $failed_at > strtotime( sprintf( '-%s', ProductSyncer::FAILURE_THRESHOLD_WINDOW ) );
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string|null
	 */
	public function get_channel_visibility( WC_Product $wc_product ): ?string {
		try {
			// todo: we might need to define visibility per variation later.
			return $this->meta_handler->get_visibility( $this->maybe_swap_for_parent( $wc_product ) );
		} catch ( InvalidValue $exception ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf( 'Channel visibility forced to "%s" for invalid product (ID: %s).', ChannelVisibility::DONT_SYNC_AND_SHOW, $wc_product->get_id() ),
				__METHOD__
			);

			return ChannelVisibility::DONT_SYNC_AND_SHOW;
		}
	}

	/**
	 * Return a string indicating sync status based on several factors.
	 *
	 * @param WC_Product $wc_product
	 *
	 * @return string|null
	 */
	public function get_sync_status( WC_Product $wc_product ): ?string {
		return $this->meta_handler->get_sync_status( $wc_product );
	}

	/**
	 * Return the string indicating the product status as reported by the Merchant Center.
	 *
	 * @param WC_Product $wc_product
	 *
	 * @return string|null
	 */
	public function get_mc_status( WC_Product $wc_product ): ?string {
		try {
			return $this->meta_handler->get_mc_status( $this->maybe_swap_for_parent( $wc_product ) );
		} catch ( InvalidValue $exception ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf( 'Product status returned null for invalid product (ID: %s).', $wc_product->get_id() ),
				__METHOD__
			);

			return null;
		}
	}

	/**
	 * If the provided product has a parent, return its ID. Otherwise, return the given (valid product) ID.
	 *
	 * @param int $product_id WooCommerce product ID.
	 *
	 * @return int The parent ID or product ID of it doesn't have a parent.
	 *
	 * @throws InvalidValue If a given ID doesn't reference a valid product. Or if a variation product does not have a
	 *                      valid parent ID (i.e. it's an orphan).
	 */
	public function maybe_swap_for_parent_id( int $product_id ): int {
		$product = $this->get_wc_product( $product_id );

		return $this->maybe_swap_for_parent( $product )->get_id();
	}

	/**
	 * If the provided product has a parent, return its parent object. Otherwise, return the given product.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 *
	 * @return WC_Product The parent product object or the given product object if it doesn't have a parent.
	 *
	 * @throws InvalidValue If a variation product does not have a valid parent ID (i.e. it's an orphan).
	 *
	 * @since 1.3.0
	 */
	public function maybe_swap_for_parent( WC_Product $product ): WC_Product {
		if ( $product instanceof WC_Product_Variation ) {
			try {
				return $this->get_wc_product( $product->get_parent_id() );
			} catch ( InvalidValue $exception ) {
				do_action(
					'woocommerce_gla_error',
					sprintf( 'An orphaned variation found (ID: %s). Please delete it via "WooCommerce > Status > Tools > Delete orphaned variations".', $product->get_id() ),
					__METHOD__
				);

				throw $exception;
			}
		}

		return $product;
	}

	/**
	 * Get validation errors for a specific product.
	 * Combines errors for variable products, which have a variation-indexed array of errors.
	 *
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public function get_validation_errors( WC_Product $product ): array {
		$errors = $this->meta_handler->get_errors( $product ) ?: [];

		$first_key = array_key_first( $errors );
		if ( ! empty( $errors ) && is_numeric( $first_key ) && 0 !== $first_key ) {
			$errors = array_unique( array_merge( ...$errors ) );
		}

		return $errors;
	}
}
