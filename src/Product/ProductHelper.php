<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use Google_Service_ShoppingContent_Product as GoogleProduct;
use WC_Product;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductHelper implements Service, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
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
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param WC                 $wc
	 */
	public function __construct( ProductMetaHandler $meta_handler, WC $wc ) {
		$this->meta_handler = $meta_handler;
		$this->wc           = $wc;
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
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$parent_product = $this->get_wc_product( $product->get_parent_id() );
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
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$parent_product = $this->get_wc_product( $product->get_parent_id() );
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
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$wc_parent_id   = $product->get_parent_id();
			$parent_product = $this->get_wc_product( $wc_parent_id );

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
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$wc_parent_id   = $product->get_parent_id();
			$parent_product = $this->get_wc_product( $wc_parent_id );
			$this->mark_as_pending( $parent_product );
		}
	}

	/**
	 * Update empty (NOT EXIST) visibility meta values to SYNC_AND_SHOW.
	 *
	 * @param WC_Product $product
	 */
	protected function update_empty_visibility( WC_Product $product ): void {
		$product    = $product instanceof WC_Product_Variation ? $this->get_wc_product( $product->get_parent_id() ) : $product;
		$visibility = $this->meta_handler->get_visibility( $product );

		if ( empty( $visibility ) ) {
			$this->meta_handler->update_visibility( $product, ChannelVisibility::SYNC_AND_SHOW );
		}
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string[] An array of Google product IDs stored for each WooCommerce product
	 */
	public function get_synced_google_product_ids( WC_Product $product ): array {
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
		$product = $this->get_wc_product( $this->get_wc_product_id( $mc_product_id ) );
		if ( ! $product ) {
			return $mc_product_id;
		}

		return $product->get_title();
	}

	/**
	 * Get WooCommerce product
	 *
	 * @param int|false $product_id
	 *
	 * @return WC_Product
	 * @throws InvalidValue If the given ID doesn't reference a valid product.
	 */
	public function get_wc_product( $product_id ): WC_Product {
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
		$product_status = $product->get_status();
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			// Check the post status of the parent product if it's a variation
			$parent         = $this->get_wc_product( $product->get_parent_id() );
			$product_status = $parent->get_status();
		}

		return ( ChannelVisibility::DONT_SYNC_AND_SHOW !== $this->get_visibility( $product ) ) &&
			   ( in_array( $product->get_type(), ProductSyncer::get_supported_product_types(), true ) ) &&
			   ( 'publish' === $product_status );
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
	 * @return string
	 */
	public function get_visibility( WC_Product $wc_product ): string {
		$visibility = $this->meta_handler->get_visibility( $wc_product );
		if ( $wc_product instanceof WC_Product_Variation ) {
			// todo: we might need to define visibility per variation later.
			$visibility = $this->meta_handler->get_visibility( $this->get_wc_product( $wc_product->get_parent_id() ) );
		}

		return $visibility;
	}

	/**
	 * Return a string indicating sync status based on several factors.
	 *
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public function get_sync_status( WC_Product $wc_product ): string {
		return $this->meta_handler->get_sync_status( $wc_product );
	}

	/**
	 * Return the string indicating the product status as reported by the Merchant Center.
	 *
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public function get_mc_status( WC_Product $wc_product ): string {
		if ( $wc_product instanceof WC_Product_Variation ) {
			return $this->meta_handler->get_mc_status( $this->get_wc_product( $wc_product->get_parent_id() ) );
		}
		return $this->meta_handler->get_mc_status( $wc_product );
	}

	/**
	 * If the provided product has a parent, return its ID. Otherwise, return the
	 * given (valid product) ID.
	 *
	 * @param int $product_id
	 *
	 * @return int The parent ID or product ID of it doesn't have a parent.
	 * @throws InvalidValue If the given ID doesn't reference a valid product.
	 */
	public function maybe_swap_for_parent_id( int $product_id ): int {
		$product = $this->get_wc_product( $product_id );
		if ( $product instanceof WC_Product_Variation ) {
			return $product->get_parent_id();
		}
		return $product_id;
	}

	/**
	 * Get validation errors for a specific product.
	 * Combines errors for variable products, which have a variation-indexed array of errors.
	 *
	 * @param WC_Product $product
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
