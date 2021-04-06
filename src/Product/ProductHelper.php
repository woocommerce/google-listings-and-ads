<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleProductService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
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

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param AttributeManager   $attribute_manager
	 */
	public function __construct( ProductMetaHandler $meta_handler, AttributeManager $attribute_manager ) {
		$this->meta_handler      = $meta_handler;
		$this->attribute_manager = $attribute_manager;
	}

	/**
	 * @param WC_Product    $product
	 * @param GoogleProduct $google_product
	 */
	public function mark_as_synced( WC_Product $product, GoogleProduct $google_product ) {
		$wc_product_id = $product->get_id();

		$this->meta_handler->update_synced_at( $wc_product_id, time() );

		// merge and update all google product ids
		$current_google_ids = $this->meta_handler->get_google_ids( $wc_product_id );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique( array_merge( $current_google_ids, [ $google_product->getTargetCountry() => $google_product->getId() ] ) );
		$this->meta_handler->update_google_ids( $wc_product_id, $google_ids );

		// check if product is synced completely and remove any previous errors if it is
		$synced_countries = array_keys( $google_ids );
		$target_countries = $this->merchant_center->get_target_countries();
		if ( count( $synced_countries ) === count( $target_countries ) && empty( array_diff( $synced_countries, $target_countries ) ) ) {
			$this->meta_handler->delete_errors( $wc_product_id );
			$this->meta_handler->delete_failed_sync_attempts( $wc_product_id );
			$this->meta_handler->delete_sync_failed_at( $wc_product_id );
		}

		// mark the parent product as synced if it's a variation
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			$this->mark_as_synced( $parent_product, $google_product );
		}
	}

	/**
	 * @param WC_Product $product
	 */
	public function mark_as_unsynced( WC_Product $product ) {
		$wc_product_id = $product->get_id();

		$this->meta_handler->delete_synced_at( $wc_product_id );
		$this->meta_handler->delete_google_ids( $wc_product_id );
		$this->meta_handler->delete_errors( $wc_product_id );
		$this->meta_handler->delete_failed_sync_attempts( $wc_product_id );
		$this->meta_handler->delete_sync_failed_at( $wc_product_id );

		// mark the parent product as un-synced if it's a variation
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			$this->mark_as_unsynced( $parent_product );
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

		$wc_product_id = $product->get_id();

		$this->meta_handler->update_errors( $wc_product_id, $errors );

		if ( ! empty( $errors[ GoogleProductService::INTERNAL_ERROR_REASON ] ) ) {
			// update failed sync attempts count in case of internal errors
			$failed_attempts = ! empty( $this->meta_handler->get_failed_sync_attempts( $wc_product_id ) ) ?
				$this->meta_handler->get_failed_sync_attempts( $wc_product_id ) :
				0;
			$this->meta_handler->update_failed_sync_attempts( $wc_product_id, $failed_attempts + 1 );
			$this->meta_handler->update_sync_failed_at( $wc_product_id, time() );
		}

		// mark the parent product as invalid if it's a variation
		if ( $product instanceof WC_Product_Variation && ! empty( $product->get_parent_id() ) ) {
			$wc_parent_id   = $product->get_parent_id();
			$parent_product = wc_get_product( $wc_parent_id );

			$parent_errors = ! empty( $this->meta_handler->get_errors( $wc_parent_id ) ) ?
				$this->meta_handler->get_errors( $wc_parent_id ) :
				[];

			$parent_errors[ $wc_product_id ] = $errors;

			$this->mark_as_invalid( $parent_product, $parent_errors );
		}
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string[] An array of Google product IDs stored for each WooCommerce product
	 */
	public function get_synced_google_product_ids( WC_Product $product ) {
		return $this->meta_handler->get_google_ids( $product->get_id() );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function is_product_synced( WC_Product $product ) {
		$synced_at  = $this->meta_handler->get_synced_at( $product->get_id() );
		$google_ids = $this->meta_handler->get_google_ids( $product->get_id() );

		return ! empty( $synced_at ) && ! empty( $google_ids );
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public function get_visibility( WC_Product $wc_product ): string {
		$visibility = $this->meta_handler->get_visibility( $wc_product->get_id() );
		if ( $wc_product instanceof WC_Product_Variation ) {
			// todo: we might need to define visibility per variation later.
			$visibility = $this->meta_handler->get_visibility( $wc_product->get_parent_id() );
		}

		return $visibility;
	}

	/**
	 * @param WC_Product $product
	 * @param string     $target_country
	 *
	 * @return WCProductAdapter
	 */
	public function generate_adapted_product( WC_Product $product, string $target_country ): WCProductAdapter {
		$attributes = $this->attribute_manager->get_all( $product->get_id() );

		return new WCProductAdapter(
			[
				'wc_product'     => $product,
				'targetCountry'  => $target_country,
				'gla_attributes' => $attributes,
			]
		);
	}
}
