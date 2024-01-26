<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Product;

defined( 'ABSPATH' ) || exit;

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
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductFilter
	 */
	protected $product_filter;

	/**
	 * ProductRepository constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param ProductFilter      $product_filter
	 */
	public function __construct( ProductMetaHandler $meta_handler, ProductFilter $product_filter ) {
		$this->meta_handler   = $meta_handler;
		$this->product_filter = $product_filter;
	}

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
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_by_ids( array $ids, array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['include'] = $ids;

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product objects already submitted to Google Merchant Center.
	 *
	 * @param array $args   Array of WooCommerce args (except 'return' and 'meta_query').
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_synced_products( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = $this->get_synced_products_meta_query();

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product IDs already submitted to Google Merchant Center.
	 *
	 * Note: Includes product variations.
	 *
	 * @param array $args  Array of WooCommerce args (except 'return' and 'meta_query').
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_synced_product_ids( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = $this->get_synced_products_meta_query();

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * @return array
	 */
	protected function get_synced_products_meta_query(): array {
		return [
			[
				'key'     => ProductMetaHandler::KEY_GOOGLE_IDS,
				'compare' => 'EXISTS',
			],
		];
	}

	/**
	 * Find and return an array of WooCommerce product objects ready to be submitted to Google Merchant Center.
	 *
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return FilteredProductList List of WooCommerce product objects after filtering.
	 */
	public function find_sync_ready_products( array $args = [], int $limit = - 1, int $offset = 0 ): FilteredProductList {
		$results = $this->find( $this->get_sync_ready_products_query_args( $args ), $limit, $offset );

		return $this->product_filter->filter_sync_ready_products( $results );
	}

	/**
	 * Find and return an array of WooCommerce product ID's ready to be deleted from the Google Merchant Center.
	 *
	 * @since 1.12.0
	 *
	 * @param int[] $ids    Array of WooCommerce product IDs
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return array
	 */
	public function find_delete_product_ids( array $ids, int $limit = - 1, int $offset = 0 ): array {
		// Default status query args in WC_Product_Query plus status trash.
		$args    = [ 'status' => [ 'draft', 'pending', 'private', 'publish', 'trash' ] ];
		$results = $this->find_by_ids( $ids, $args, $limit, $offset );
		return $this->product_filter->filter_products_for_delete( $results )->get_product_ids();
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
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 *
	 * @return array
	 */
	protected function get_sync_ready_products_query_args( array $args = [] ): array {
		$args['meta_query'] = $this->get_sync_ready_products_meta_query();

		// don't include variable products in query
		$args['type'] = array_diff( ProductSyncer::get_supported_product_types(), [ 'variable' ] );

		// only include published products
		if ( empty( $args['status'] ) ) {
			$args['status'] = [ 'publish' ];
		}

		return $args;
	}

	/**
	 * @return array
	 */
	protected function get_valid_products_meta_query(): array {
		return [
			'relation' => 'OR',
			[
				'key'     => ProductMetaHandler::KEY_ERRORS,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => ProductMetaHandler::KEY_ERRORS,
				'compare' => '=',
				'value'   => '',
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
	public function find_expiring_product_ids( int $limit = - 1, int $offset = 0 ): array {
		$args['meta_query'] = [
			'relation' => 'AND',
			$this->get_sync_ready_products_meta_query(),
			$this->get_valid_products_meta_query(),
			[
				[
					'key'     => ProductMetaHandler::KEY_SYNCED_AT,
					'compare' => '<',
					'value'   => strtotime( '-25 days' ),
				],
			],
		];

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * Find and return an array of WooCommerce product IDs that are marked as MC not_synced.
	 * Excludes variations and variable products without variations.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_mc_not_synced_product_ids( int $limit = -1, int $offset = 0 ): array {
		$types = ProductSyncer::get_supported_product_types();
		$types = array_diff( $types, [ 'variation' ] );
		$args  = [
			'status'     => 'publish',
			'type'       => $types,
			'meta_query' => [
				[
					'key'     => ProductMetaHandler::KEY_SYNC_STATUS,
					'compare' => '!=',
					'value'   => SyncStatus::SYNCED,
				],
				[
					'key'     => ProductMetaHandler::KEY_VISIBILITY,
					'compare' => '=',
					'value'   => ChannelVisibility::SYNC_AND_SHOW,
				],
			],
		];

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * Returns an array of Google Product IDs associated with all synced WooCommerce products.
	 * Note: excludes variable parent products as only the child variation products are actually synced
	 * to Merchant Center
	 *
	 * @since 1.1.0
	 *
	 * @return array Google Product IDS
	 */
	public function find_all_synced_google_ids(): array {
		// Don't include variable parent products as they aren't actually synced to Merchant Center.
		$args['type']        = array_diff( ProductSyncer::get_supported_product_types(), [ 'variable' ] );
		$synced_product_ids  = $this->find_synced_product_ids( $args );
		$google_ids_meta_key = $this->prefix_meta_key( ProductMetaHandler::KEY_GOOGLE_IDS );
		$synced_google_ids   = [];
		foreach ( $synced_product_ids as $product_id ) {
			$meta_google_ids = get_post_meta( $product_id, $google_ids_meta_key, true );
			if ( ! is_array( $meta_google_ids ) ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Invalid Google IDs retrieve for product %d', $product_id ),
					__METHOD__
				);
				continue;
			}
			$synced_google_ids = array_merge( $synced_google_ids, array_values( $meta_google_ids ) );
		}
		return $synced_google_ids;
	}

	/**
	 * Find and return an array of WooCommerce products based on the provided arguments.
	 *
	 * @param array $args   Array of WooCommerce args (see below), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @link https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @see ProductMetaHandler::TYPES For the list of meta data that can be used as query arguments.
	 *
	 * @return WC_Product[]|int[] Array of WooCommerce product objects or IDs, depending on the 'return' argument.
	 */
	protected function execute_woocommerce_query( array $args = [], int $limit = -1, int $offset = 0 ): array {
		$args['limit']  = $limit;
		$args['offset'] = $offset;

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
			$args['meta_query'] = $this->meta_handler->prefix_meta_query_keys( $args['meta_query'] );
		}

		// only include supported product types
		if ( empty( $args['type'] ) ) {
			$args['type'] = ProductSyncer::get_supported_product_types();
		}

		// use no ordering unless specified in arguments. overrides the default WooCommerce query args
		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'none';
		}

		$args = apply_filters( 'woocommerce_gla_product_query_args', $args );

		return $args;
	}
}
