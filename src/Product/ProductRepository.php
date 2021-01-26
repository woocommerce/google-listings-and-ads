<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Product;

/**
 * Class ProductRepository
 *
 * Contains methods to find and retrieve products from database.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductRepository implements Service {

	use PluginHelper;

	/**
	 * Find and return an array of WooCommerce product objects based on the provided arguments.
	 *
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find( array $args = [] ): array {
		$args['return'] = 'objects';

		return $this->execute_woocommerce_query( $args );
	}

	/**
	 * Find and return an array of WooCommerce product IDs based on the provided arguments.
	 *
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_ids( array $args = [] ): array {
		$args['return'] = 'ids';

		return $this->execute_woocommerce_query( $args );
	}

	/**
	 * Find and return an array of WooCommerce product objects based on the provided product IDs.
	 *
	 * @param int[] $ids Array of WooCommerce product IDs
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_by_ids( array $ids = [] ): array {
		$args['include'] = $ids;

		return $this->find( $args );
	}

	/**
	 * Find and return an array of WooCommerce product IDs already submitted to Google Merchant Center.
	 *
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_synced_product_ids( array $args = [] ): array {
		$google_id_key          = ProductMetaHandler::KEY_GOOGLE_IDS;
		$compare_key            = ProductMetaHandler::get_meta_compare_key( $google_id_key );
		$args[ $google_id_key ] = '';
		$args[ $compare_key ]   = 'EXISTS';

		return $this->find_ids( $args );
	}

	/**
	 * Find and return an array of WooCommerce products based on the provided arguments.
	 *
	 * @param array $args Array of WooCommerce args (see below), and product metadata.
	 * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @see ProductMetaHandler::VALID_KEYS For the list of meta data that can be used as query arguments.
	 *
	 * @return WC_Product[]|int[] Array of WooCommerce product objects or IDs, depending on the 'return' argument.
	 */
	protected function execute_woocommerce_query( array $args = [] ): array {
		return wc_get_products( $this->prepare_query_args( $args ) );
	}

	/**
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return array
	 */
	protected function prepare_query_args( array $args = [] ): array {
		if ( empty( $args ) ) {
			return [];
		}

		$valid_keys = array_map( [ ProductMetaHandler::class, 'get_meta_compare_key' ], ProductMetaHandler::VALID_KEYS );
		$valid_keys = array_merge( $valid_keys, ProductMetaHandler::VALID_KEYS );

		$args_meta_keys = array_intersect( $valid_keys, array_keys( $args ) );
		foreach ( $args_meta_keys as $key ) {
			$args[ $this->prefix_meta_key( $key ) ] = $args[ $key ];
			unset( $args[ $key ] );
		}

		return $args;
	}
}
