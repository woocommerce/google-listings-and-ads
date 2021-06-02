<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
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
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * ProductRepository constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 */
	public function __construct( ProductMetaHandler $meta_handler ) {
		$this->meta_handler = $meta_handler;
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
	 * Find and return an array of WooCommerce product objects already submitted to Google Merchant Center.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_synced_products( int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = $this->get_synced_products_meta_query();

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
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_sync_ready_products( array $args = [], int $limit = - 1, int $offset = 0 ): array {
		$results = $this->find( $this->get_sync_ready_products_query_args( $args ), $limit, $offset );

		return $this->filter_sync_ready_products( $results, false );
	}

	/**
	 * Find and return an array of WooCommerce product IDs ready to be submitted to Google Merchant Center.
	 *
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_sync_ready_product_ids( array $args = [], int $limit = - 1, int $offset = 0 ): array {
		$results = $this->find( $this->get_sync_ready_products_query_args( $args ), $limit, $offset );

		return $this->filter_sync_ready_products( $results, true );
	}

	/**
	 * Filters and returns a list of products that are ready to be submitted to Google Merchant Center.
	 *
	 * @param WC_Product[] $products
	 * @param bool         $return_ids
	 *
	 * @return WC_Product[]
	 */
	protected function filter_sync_ready_products( array $products, bool $return_ids = false ): array {
		/**
		 * Filters the list of products ready to be synced.
		 *
		 * @param WC_Product[] $products Sync-ready WooCommerce products
		 */
		$products = apply_filters( 'gla_get_sync_ready_products', $products );

		// skip products that have recently failed to sync.
		$results = [];
		foreach ( $products as $product ) {
			$failed_attempts = $this->meta_handler->get_failed_sync_attempts( $product->get_id() );
			$failed_at       = $this->meta_handler->get_sync_failed_at( $product->get_id() );

			// if it has failed less times than the specified threshold OR if syncing it hasn't failed within the specified window
			if (
				empty( $failed_attempts ) ||
				empty( $failed_at ) ||
				$failed_attempts <= ProductSyncer::FAILURE_THRESHOLD ||
				$failed_at <= strtotime( sprintf( '-%s', ProductSyncer::FAILURE_THRESHOLD_WINDOW ) )
			) {

				$results[] = $return_ids ? $product->get_id() : $product;
			}
		}

		return $results;
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
	 * Find and return an array of WooCommerce product IDs already awaiting sync to Google Merchant Center.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_sync_pending_product_ids( int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = [
			[
				'key'     => ProductMetaHandler::KEY_GOOGLE_IDS,
				'compare' => 'NOT EXISTS',
			],
			$this->get_sync_ready_products_meta_query(),
		];

		return $this->find_ids( $args, $limit, $offset );
	}

	/**
	 * @param array $args Array of WooCommerce args (except 'return'), and product metadata.
	 *
	 * @return array
	 */
	protected function get_sync_ready_products_query_args( array $args = [] ): array {
		$args['meta_query'] = $this->get_sync_ready_products_meta_query();

		// don't include variable products in query
		$args['type']        = $this->get_supported_product_types();
		$variable_type_index = array_search( 'variable', $args['type'], true );
		if ( false !== $variable_type_index ) {
			unset( $args['type'][ $variable_type_index ] );
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
					'value'   => strtotime( '-28 days' ),
				],
			],
		];

		return $this->find_ids( $args, $limit, $offset );
	}



	/**
	 * Find and return an array of WooCommerce product objects that are pending synchronization,
	 * but have failed pre-sync validation.
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_presync_error_products( int $limit = -1, int $offset = 0 ): array {
		$args['meta_query'] = [
			'relation' => 'AND',
			$this->get_sync_ready_products_meta_query(),
			[
				[
					'key'     => ProductMetaHandler::KEY_SYNC_STATUS,
					'compare' => '=',
					'value'   => SyncStatus::HAS_ERRORS,
				],
			],
		];

		return $this->find( $args, $limit, $offset );
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
			$args['type'] = $this->get_supported_product_types();
		}

		return $args;
	}

	/**
	 * Return the list of supported product types.
	 *
	 * @return array
	 */
	public function get_supported_product_types(): array {
		return (array) apply_filters( 'woocommerce_gla_supported_product_types', [ 'simple', 'variable', 'variation' ] );
	}

}
