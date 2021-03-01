<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\BatchProductRequestEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class BatchProductHelper
 *
 * Contains helper methods for batch processing products.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class BatchProductHelper implements Service {

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * BatchProductHelper constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param ProductHelper      $product_helper
	 */
	public function __construct( ProductMetaHandler $meta_handler, ProductHelper $product_helper ) {
		$this->meta_handler   = $meta_handler;
		$this->product_helper = $product_helper;
	}

	/**
	 * Filters and returns only the products already synced with Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return WC_Product[] The synced products.
	 */
	public function filter_synced_products( array $products ): array {
		return array_filter( $products, [ $this->product_helper, 'is_product_synced' ] );
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	public function mark_as_synced( BatchProductEntry $product_entry ) {
		$wc_product_id  = $product_entry->get_wc_product_id();
		$google_product = $product_entry->get_google_product();

		$this->meta_handler->update_synced_at( $wc_product_id, time() );

		// merge and update all google product ids
		$current_google_ids = $this->meta_handler->get_google_ids( $wc_product_id );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique( array_merge( $current_google_ids, [ $google_product->getId() ] ) );
		$this->meta_handler->update_google_ids( $wc_product_id, $google_ids );

		// mark the parent product as synced if it's a variation
		$wc_product = wc_get_product( $wc_product_id );
		if ( $wc_product instanceof WC_Product_Variation && ! empty( $wc_product->get_parent_id() ) ) {
			$this->mark_as_synced( new BatchProductEntry( $wc_product->get_parent_id(), $google_product ) );
		}
	}

	/**
	 * @param BatchProductEntry $product_entry
	 */
	public function mark_as_unsynced( BatchProductEntry $product_entry ) {
		$wc_product_id = $product_entry->get_wc_product_id();
		$this->meta_handler->delete_synced_at( $wc_product_id );
		$this->meta_handler->delete_google_ids( $wc_product_id );

		// mark the parent product as un-synced if it's a variation
		$wc_product = wc_get_product( $wc_product_id );
		if ( $wc_product instanceof WC_Product_Variation && ! empty( $wc_product->get_parent_id() ) ) {
			$this->mark_as_unsynced( new BatchProductEntry( $wc_product->get_parent_id(), null ) );
		}
	}

	/**
	 * Generates an array map containing the Google product IDs as key and the WooCommerce product IDs as values.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return BatchProductRequestEntry[]
	 */
	public function generate_delete_request_entries( array $products ): array {
		$request_entries = [];
		$products        = self::expand_variations( $products );
		foreach ( $products as $product ) {
			$google_ids = $this->product_helper->get_synced_google_product_ids( $product );
			if ( empty( $google_ids ) ) {
				continue;
			}

			$product_entries = array_map(
				function ( string $google_id ) use ( $product ) {
					return new BatchProductRequestEntry(
						$product->get_id(),
						$google_id
					);
				},
				$google_ids
			);
			$request_entries = array_merge( $request_entries, $product_entries );
		}

		return $request_entries;
	}

	/**
	 * @param BatchProductRequestEntry[] $request_entries
	 *
	 * @return string[] Array of Google product IDs mapped to WooCommerce product IDs
	 */
	public function request_entries_to_id_map( array $request_entries ): array {
		$id_map = [];
		foreach ( $request_entries as $request_entry ) {
			$id_map[ $request_entry->get_wc_product_id() ] = $request_entry->get_product();
		}

		return $id_map;
	}

	/**
	 * Fetch and return all of the available product variations next to the given products.
	 *
	 * @param WC_Product[] $products
	 *
	 * @return WC_Product[]
	 *
	 * @throws InvalidValue If an invalid product is provided.
	 */
	public static function expand_variations( array $products ): array {
		$all_products = [];
		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				throw InvalidValue::not_instance_of( WC_Product::class, 'product' );
			}

			if ( $product instanceof WC_Product_Variable ) {
				$variations = $product->get_available_variations( 'objects' );
				foreach ( $variations as $variation ) {
					$all_products[ $variation->get_id() ] = $variation;
				}
			} else {
				$all_products[ $product->get_id() ] = $product;
			}
		}

		return $all_products;
	}
}
