<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Product;
use WC_Product_Variable;

/**
 * Class ProductHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductHelper implements Service {

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( ProductMetaHandler $meta_handler ) {
		$this->meta_handler = $meta_handler;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function get_synced_google_product_id( WC_Product $product ) {
		return $this->meta_handler->get_google_id( $product->get_id() );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function is_product_synced( WC_Product $product ) {
		$synced_at = $this->meta_handler->get_synced_at( $product->get_id() );
		$google_id = $this->meta_handler->get_google_id( $product->get_id() );

		return ! empty( $synced_at ) && ! empty( $google_id );
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return WCProductAdapter
	 */
	public static function generate_adapted_product( WC_Product $product ): WCProductAdapter {
		return new WCProductAdapter( [ 'wc_product' => $product ] );
	}

	/**
	 * Fetch and return all of the available product variations next to the given products.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return WC_Product[]
	 */
	public static function expand_variations( array $products ): array {
		$all_products = [];
		foreach ( $products as $product ) {
			$all_products[] = $product;
			if ( $product instanceof WC_Product_Variable ) {
				$all_products = array_merge( $all_products, $product->get_available_variations( 'objects' ) );
			}
		}

		return $all_products;
	}
}
