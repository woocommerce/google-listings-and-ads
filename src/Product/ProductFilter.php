<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Product;

/**
 * Class ProductFilter
 *
 * Filters a list of products retrieved from the repository.
 *
 * @since 1.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductFilter implements Service {

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * ProductRepository constructor.
	 *
	 * @param ProductHelper $product_helper
	 */
	public function __construct( ProductHelper $product_helper ) {
		$this->product_helper = $product_helper;
	}

	/**
	 * Filters and returns a list of products that are ready to be submitted to Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return FilteredProductList
	 */
	public function filter_sync_ready_products( array $products ): FilteredProductList {
		$unfiltered_count = count( $products );
		/**
		 * Filters the list of products ready to be synced (before applying filters to check failures and sync-ready status).
		 *
		 * @param WC_Product[] $products Sync-ready WooCommerce products
		 */
		$products = apply_filters( 'woocommerce_gla_get_sync_ready_products_pre_filter', $products );
		$results  = array_values(
			array_filter(
				$products,
				function ( $product ) {
					return $this->product_helper->is_sync_ready( $product ) && ! $this->product_helper->is_sync_failed_recently( $product );
				}
			)
		);
		/**
		 * Filters the list of products ready to be synced (after applying filters to check failures and sync-ready status).
		 *
		 * @param WC_Product[] $results Sync-ready WooCommerce products
		 */
		$results = apply_filters( 'woocommerce_gla_get_sync_ready_products_filter', $results );

		return new FilteredProductList( $results, $unfiltered_count );
	}

	/**
	 * Filter and return a list of products that can be deleted.
	 *
	 * @since 1.12.0
	 *
	 * @param WC_Product[] $products
	 *
	 * @return FilteredProductList
	 */
	public function filter_products_for_delete( array $products ): FilteredProductList {
		$results = array_values(
			array_filter(
				$products,
				function ( $product ) {
					return ! $this->product_helper->is_delete_failed_threshold_reached( $product );
				}
			)
		);

		return new FilteredProductList( $results, count( $products ) );
	}
}
