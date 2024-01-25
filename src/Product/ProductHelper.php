<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Product as GoogleProduct;
use WC_Product;
use WC_Product_Variation;
use WP_Post;

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
	 * @var TargetAudience
	 */
	protected $target_audience;

	/**
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param WC                 $wc
	 * @param TargetAudience     $target_audience
	 */
	public function __construct( ProductMetaHandler $meta_handler, WC $wc, TargetAudience $target_audience ) {
		$this->meta_handler    = $meta_handler;
		$this->wc              = $wc;
		$this->target_audience = $target_audience;
	}

	/**
	 * Mark a product as synced in the local database.
	 * This function also handles the following cleanup tasks:
	 * - Remove any failed delete attempts
	 * - Update the visibility (if it was previously empty)
	 * - Remove any previous product errors (if it was synced for all target countries)
	 *
	 * @param WC_Product    $product
	 * @param GoogleProduct $google_product
	 */
	public function mark_as_synced( WC_Product $product, GoogleProduct $google_product ) {
		$this->meta_handler->delete_failed_delete_attempts( $product );
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
		$target_countries = $this->target_audience->get_target_countries();
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
		if ( ! $this->is_sync_ready( $product ) ) {
			$this->meta_handler->delete_sync_status( $product );
		} else {
			$this->meta_handler->update_sync_status( $product, SyncStatus::NOT_SYNCED );
		}
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
	 * Update a product's channel visibility.
	 *
	 * @param WC_Product $product
	 * @param string     $visibility
	 */
	public function update_channel_visibility( WC_Product $product, string $visibility ): void {
		try {
			$product = $this->maybe_swap_for_parent( $product );
		} catch ( InvalidValue $exception ) {
			// The error has been logged within the call of maybe_swap_for_parent
			return;
		}

		try {
			$visibility = ChannelVisibility::cast( $visibility )->get();
		} catch ( InvalidValue $exception ) {
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
			return;
		}

		$this->meta_handler->update_visibility( $product, $visibility );
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
	 * @param string $mc_product_id Simple product ID (`merchant_center_id`) or
	 *                              namespaced product ID (`online:en:GB:merchant_center_id`)
	 *
	 * @return int the ID for the WC product linked to the provided Google product ID (0 if not found)
	 */
	public function get_wc_product_id( string $mc_product_id ): int {
		// Maybe remove everything before the last colon ':'
		$mc_product_id_tokens = explode( ':', $mc_product_id );
		$mc_product_id        = end( $mc_product_id_tokens );

		$wc_product_id = 0;
		$pattern       = '/' . preg_quote( $this->get_slug(), '/' ) . '_(\d+)$/';
		if ( preg_match( $pattern, $mc_product_id, $matches ) ) {
			$wc_product_id = (int) $matches[1];
		}

		/**
		 * Filters the WooCommerce product ID that was determined to be associated with the
		 * given Merchant Center product ID.
		 *
		 * @param string $wc_product_id The WooCommerce product ID as determined by default.
		 * @param string $mc_product_id Simple Merchant Center product ID (without any prefixes).
		 * @since 2.4.6
		 *
		 * @return string Merchant Center product ID as normally generated by the plugin (e.g., gla_1234).
		 */
		return (int) apply_filters( 'woocommerce_gla_get_wc_product_id', $wc_product_id, $mc_product_id );
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
	 * Get WooCommerce product by WP get_post
	 *
	 * @param int $product_id
	 *
	 * @return WP_Post|null
	 */
	public function get_wc_product_by_wp_post( int $product_id ): ?WP_Post {
		return get_post( $product_id );
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
	 * Increment failed delete attempts.
	 *
	 * @since 1.12.0
	 *
	 * @param WC_Product $product
	 */
	public function increment_failed_delete_attempt( WC_Product $product ) {
		$failed_attempts = $this->meta_handler->get_failed_delete_attempts( $product ) ?? 0;
		$this->meta_handler->update_failed_delete_attempts( $product, $failed_attempts + 1 );
	}

	/**
	 * Whether deleting has failed more times than the specified threshold.
	 *
	 * @since 1.12.0
	 *
	 * @param WC_Product $product
	 *
	 * @return boolean
	 */
	public function is_delete_failed_threshold_reached( WC_Product $product ): bool {
		$failed_attempts = $this->meta_handler->get_failed_delete_attempts( $product ) ?? 0;
		return $failed_attempts >= ProductSyncer::FAILURE_THRESHOLD;
	}

	/**
	 * Increment failed delete attempts.
	 *
	 * @since 1.12.2
	 *
	 * @param WC_Product $product
	 */
	public function increment_failed_update_attempt( WC_Product $product ) {
		$failed_attempts = $this->meta_handler->get_failed_sync_attempts( $product ) ?? 0;
		$this->meta_handler->update_failed_sync_attempts( $product, $failed_attempts + 1 );
	}

	/**
	 * Whether deleting has failed more times than the specified threshold.
	 *
	 * @since 1.12.2
	 *
	 * @param WC_Product $product
	 *
	 * @return boolean
	 */
	public function is_update_failed_threshold_reached( WC_Product $product ): bool {
		$failed_attempts = $this->meta_handler->get_failed_sync_attempts( $product ) ?? 0;
		return $failed_attempts >= ProductSyncer::FAILURE_THRESHOLD;
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
	 * If an item from the provided list of products has a parent, replace it with the parent ID.
	 *
	 * @param int[] $product_ids                        A list of WooCommerce product ID.
	 * @param bool  $check_product_status    (Optional) Check if the product status is publish.
	 * @param bool  $ignore_product_on_error (Optional) Ignore the product when invalid value error occurs.
	 *
	 * @return int[] A list of parent ID or product ID if it doesn't have a parent.
	 *
	 * @throws InvalidValue If the given param ignore_product_on_error is false and any of a given ID doesn't reference a valid product.
	 *                      Or if a variation product does not have a valid parent ID (i.e. it's an orphan).
	 *
	 * @since 2.2.0
	 */
	public function maybe_swap_for_parent_ids( array $product_ids, bool $check_product_status = true, bool $ignore_product_on_error = true ) {
		$new_product_ids = [];

		foreach ( $product_ids as $index => $product_id ) {
			try {
				$product     = $this->get_wc_product( $product_id );
				$new_product = $this->maybe_swap_for_parent( $product );
				if ( ! $check_product_status || 'publish' === $new_product->get_status() ) {
					$new_product_ids[ $index ] = $new_product->get_id();
				}
			} catch ( InvalidValue $exception ) {
				if ( ! $ignore_product_on_error ) {
					throw $exception;
				}
			}
		}

		return array_unique( $new_product_ids );
	}

	/**
	 * If the provided product has a parent, return its ID. Otherwise, return the given (valid product) ID.
	 *
	 * @param int $product_id WooCommerce product ID.
	 *
	 * @return int The parent ID or product ID if it doesn't have a parent.
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

	/**
	 * Get categories list for a specific product.
	 *
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public function get_categories( WC_Product $product ): array {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		return ( empty( $terms ) || is_wp_error( $terms ) ) ? [] : wp_list_pluck( $terms, 'name' );
	}
}
