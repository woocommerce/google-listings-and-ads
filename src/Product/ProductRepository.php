<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use WC_Product;

/**
 * Class ProductRepository
 *
 * Contains methods to find and retrieve products from database.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductRepository implements Service {

	public const INCLUDE_VARIATIONS_KEY = 'include_variations';

	use PluginHelper;

	/**
	 * Find and return an array of WooCommerce product objects based on the provided arguments.
	 *
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['return'] = 'objects';

		return $this->execute_woocommerce_query( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product IDs based on the provided arguments.
	 *
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_ids( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['return'] = 'ids';

		return $this->execute_woocommerce_query( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product objects based on the provided product IDs.
	 *
	 * @param int[] $ids    Array of WooCommerce product IDs
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_by_ids( array $ids, int $limit = -1, int $offset = 0 ): array {
		$args['include'] = $ids;

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product objects based on the provided product IDs.
	 *
	 * Note: Including product variations.
	 *
	 * @param int[] $ids    Array of WooCommerce product IDs
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_by_ids_including_variations( array $ids, int $limit = -1, int $offset = 0 ): array {
		$args['include'] = $ids;

		// include product variations
		$args[ self::INCLUDE_VARIATIONS_KEY ] = true;

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product objects already submitted to Google Merchant Center.
	 *
	 * Note: Includes product variations.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_synced_products( int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = [
			[
				'key'     => ProductMetaHandler::KEY_GOOGLE_IDS,
				'compare' => 'EXISTS',
			],
		];

		// include product variations
		$args[ self::INCLUDE_VARIATIONS_KEY ] = true;

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product IDs already submitted to Google Merchant Center.
	 *
	 * Note: Includes product variations.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_synced_product_ids( int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = [
			[
				'key'     => ProductMetaHandler::KEY_GOOGLE_IDS,
				'compare' => 'EXISTS',
			],
		];

		// include product variations
		$args[ self::INCLUDE_VARIATIONS_KEY ] = true;

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product objects ready to be submitted to Google Merchant Center.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_sync_ready_products( int $limit = - 1, int $offset = 0 ): array {
		$args['meta_query'] = $this->get_sync_ready_products_meta_query();

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product IDs ready to be submitted to Google Merchant Center.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_sync_ready_product_ids( int $limit = - 1, int $offset = 0 ): array {
		$args['meta_query'] = $this->get_sync_ready_products_meta_query();

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * @return array
	 */
	protected function get_sync_ready_products_meta_query(): array {
		return [
			'relation' => 'OR',
			[
				'key'     => ProductMetaHandler::KEY_VISIBILITY,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => ProductMetaHandler::KEY_VISIBILITY,
				'compare' => '!=',
				'value'   => ChannelVisibility::DONT_SYNC_AND_SHOW,
			],
		];
	}

	/**
	 * Find and return an array of WooCommerce product IDs nearly expired and ready to be re-submitted to Google Merchant Center.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_nearly_expired_product_ids( int $limit = - 1, int $offset = 0 ): array {
		$args['meta_query'] = [
			'relation' => 'AND',
			[
				'relation' => 'OR',
				[
					'key'     => ProductMetaHandler::KEY_VISIBILITY,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => ProductMetaHandler::KEY_VISIBILITY,
					'compare' => '!=',
					'value'   => ChannelVisibility::DONT_SYNC_AND_SHOW,
				],
			],
			[
				'relation' => 'OR',
				[
					'key'     => ProductMetaHandler::KEY_SYNCED_AT,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => ProductMetaHandler::KEY_SYNCED_AT,
					'compare' => '<',
					'value'   => strtotime( '-28 days' ),
				],
			],
		];

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce products based on the provided arguments.
	 *
	 * @param array $args   Array of WooCommerce args (see below), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @see ProductMetaHandler::VALID_KEYS For the list of meta data that can be used as query arguments.
	 *
	 * @return WC_Product[]|int[] Array of WooCommerce product objects or IDs, depending on the 'return' argument.
	 */
	protected function execute_woocommerce_query( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['limit']  = $limit;
		$args['offset'] = $offset;

		// include product variations in the query
		if ( isset( $args[ self::INCLUDE_VARIATIONS_KEY ] ) && true === $args[ self::INCLUDE_VARIATIONS_KEY ] ) {
			$args['type'] = array_merge( array_keys( wc_get_product_types() ), [ 'variation' ] );
		}

		return wc_get_products( $this->prepare_query_args( $args ) );
	}

	/**
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 *
	 * @see execute_woocommerce_query For more information about the arguments.
	 *
	 * @return array
	 */
	protected function prepare_query_args( array $args = [] ): array {
		if ( empty( $args ) ) {
			return [];
		}

		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query'] = $this->prefix_meta_query_keys( $args['meta_query'] );
		}

		return $args;
	}

	/**
	 * @param array $meta_queries
	 *
	 * @return array
	 */
	protected function prefix_meta_query_keys( array $meta_queries ): array {
		$updated_queries = [];
		if ( ! is_array( $meta_queries ) ) {
			return $updated_queries;
		}

		foreach ( $meta_queries as $key => $meta_query ) {
			// First-order clause.
			if ( 'relation' === $key && is_string( $meta_query ) ) {
				$updated_queries[ $key ] = $meta_query;

				// First-order clause.
			} elseif ( ( isset( $meta_query['key'] ) || isset( $meta_query['value'] ) ) && in_array( $meta_query['key'], ProductMetaHandler::VALID_KEYS, true ) ) {
				$meta_query['key'] = $this->prefix_meta_key( $meta_query['key'] );
			} else {
				// Otherwise, it's a nested meta_query, so we recurse.
				$meta_query = $this->prefix_meta_query_keys( $meta_query );
			}

			$updated_queries[ $key ] = $meta_query;
		}

		return $updated_queries;
	}
}
