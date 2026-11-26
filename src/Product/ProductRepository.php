<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
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
	 * Products whose synced_at is older than this many days are treated as nearly expired
	 * and re-submitted (see find_expiring_product_ids). The delta-sync freshness window is
	 * clamped to this so an unchanged product can never be skipped past its resubmission point.
	 * Read the window through get_resubmit_expiry_days(), which applies the
	 * woocommerce_gla_resubmit_expiry_days filter.
	 */
	public const RESUBMIT_EXPIRY_DAYS = 25;

	/**
	 * @var ProductMetaHandler
	 */
	protected $meta_handler;

	/**
	 * @var ProductFilter
	 */
	protected $product_filter;

	/**
	 * @var WPML
	 */
	protected $wpml;

	/**
	 * ProductRepository constructor.
	 *
	 * @param ProductMetaHandler $meta_handler
	 * @param ProductFilter      $product_filter
	 * @param WPML               $wpml
	 */
	public function __construct( ProductMetaHandler $meta_handler, ProductFilter $product_filter, WPML $wpml ) {
		$this->meta_handler   = $meta_handler;
		$this->product_filter = $product_filter;
		$this->wpml           = $wpml;
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
		// If no product IDs are supplied then return early to avoid querying and loading every product.
		if ( empty( $ids ) ) {
			return [];
		}

		$args['include'] = $ids;

		return $this->find( $args, $limit, $offset );
	}

	/**
	 * Find and return an associative array of products with the product ID as the key.
	 *
	 * @param int[] $ids    Array of WooCommerce product IDs
	 * @param array $args   Array of WooCommerce args (except 'return'), and product metadata.
	 * @param int   $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int   $offset Amount to offset product results.
	 *
	 * @return WC_Product[] Array of WooCommerce product objects
	 */
	public function find_by_ids_as_associative_array( array $ids, array $args = [], int $limit = -1, int $offset = 0 ): array {
		$products = $this->find_by_ids( $ids, $args, $limit, $offset );
		$map      = [];
		foreach ( $products as $product ) {
			$map[ $product->get_id() ] = $product;
		}
		return $map;
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
	 * Find and return an array of WooCommerce product IDs already submitted to Google Merchant
	 * Center, ordered by ID ascending and starting strictly after the given cursor.
	 *
	 * Uses keyset (cursor) pagination like find_expiring_product_ids(), rather than
	 * find_synced_product_ids()'s OFFSET-based paging. That matters here specifically because a
	 * caller paging through synced products in order to unsync or delete some of them (a cleanup
	 * job) shrinks the very result set the query filters on as it goes; OFFSET-based paging would
	 * silently skip rows whenever earlier batches removed matches, since each new page still skips
	 * a fixed count from the start rather than resuming after the last row actually seen.
	 *
	 * @since 3.9.0
	 *
	 * @param int $last_id The last product ID processed in the previous batch (0 to start from the beginning).
	 * @param int $limit   Maximum number of results to retrieve or -1 for unlimited.
	 *
	 * @return int[] Array of WooCommerce product IDs ordered by ID ASC.
	 */
	public function find_synced_product_ids_after_id( int $last_id = 0, int $limit = -1 ): array {
		global $wpdb;

		$args = [
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => $this->get_synced_products_meta_query(),
		];

		// Add a temporary WHERE clause to implement keyset pagination (ID > $last_id).
		$cursor_filter = function ( string $where ) use ( $wpdb, $last_id ): string {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $last_id );
		};

		add_filter( 'posts_where', $cursor_filter );
		try {
			$results = $this->find_ids( $args, $limit );
		} finally {
			remove_filter( 'posts_where', $cursor_filter );
		}

		return $results;
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
	 * The SQL in find_expiring_product_ids() mirrors these visibility rules. Keep the two in step.
	 *
	 * @return array
	 */
	public function get_sync_ready_products_meta_query(): array {
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
	 * The SQL in find_expiring_product_ids() mirrors these errors rules. Keep the two in step.
	 *
	 * @deprecated 3.11.0 No longer called: find_expiring_product_ids(), its only caller, now checks
	 *             the errors meta in SQL. Kept for subclasses that call or override it.
	 *
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
	 * Uses keyset (cursor) pagination: instead of OFFSET (which must scan and skip rows), this method
	 * uses "WHERE ID > $last_id ORDER BY ID ASC" so each batch starts exactly where the previous one
	 * left off at O(log n) cost regardless of how deep into the result set we are.
	 *
	 * Only the synced_at condition goes through meta_query, which turns it into a single postmeta
	 * join that matches one row per product. The visibility and errors conditions are added to the
	 * WHERE clause as NOT EXISTS lookups by meta key. As meta_query clauses they need NOT EXISTS
	 * and OR, which makes WP_Meta_Query LEFT JOIN postmeta on post ID alone: every meta row of a
	 * product then multiplies the rows the database reads, and large stores stalled for hours.
	 *
	 * Variations skip the publish status check because ProductHelper::is_sync_ready() judges a
	 * variation by its parent's status. The query can therefore return disabled variations, which
	 * is_sync_ready() still rejects by default (is_visible() and woocommerce_hide_invisible_variations).
	 *
	 * @param int $last_id The last product ID processed in the previous batch (0 to start from the beginning).
	 * @param int $limit   Maximum number of results to retrieve or -1 for unlimited.
	 *
	 * @return int[] Array of WooCommerce product IDs ordered by ID ASC.
	 */
	public function find_expiring_product_ids( int $last_id = 0, int $limit = -1 ): array {
		global $wpdb;

		$args = [
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => [
				[
					'key'     => ProductMetaHandler::KEY_SYNCED_AT,
					'compare' => '<',
					'value'   => strtotime( '-' . self::get_resubmit_expiry_days() . ' days' ),
				],
			],
		];

		$visibility_key = $this->prefix_meta_key( ProductMetaHandler::KEY_VISIBILITY );
		$errors_key     = $this->prefix_meta_key( ProductMetaHandler::KEY_ERRORS );

		// The NOT EXISTS lookups mirror get_sync_ready_products_meta_query() (visibility) and
		// get_valid_products_meta_query() (errors). Keep the rules in step when changing either.
		$where_filter = function ( string $where ) use ( $wpdb, $last_id, $visibility_key, $errors_key ): string {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $where . $wpdb->prepare(
				" AND {$wpdb->posts}.ID > %d
				AND ( {$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_type = 'product_variation' )
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} AS gla_visibility
					WHERE gla_visibility.post_id = {$wpdb->posts}.ID AND gla_visibility.meta_key = %s AND gla_visibility.meta_value = %s
				)
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} AS gla_errors
					WHERE gla_errors.post_id = {$wpdb->posts}.ID AND gla_errors.meta_key = %s AND gla_errors.meta_value <> ''
				)",
				$last_id,
				$visibility_key,
				ChannelVisibility::DONT_SYNC_AND_SHOW,
				$errors_key
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		};

		add_filter( 'posts_where', $where_filter );
		try {
			$results = $this->find_ids( $args, $limit );
		} finally {
			remove_filter( 'posts_where', $where_filter );
		}

		return $results;
	}

	/**
	 * Get the number of days after its last sync that a product is resubmitted to Merchant Center.
	 *
	 * Google expires a product 30 days after its last update, so the filtered value can shorten
	 * the window but not lengthen it past RESUBMIT_EXPIRY_DAYS.
	 *
	 * @since 3.11.0
	 *
	 * @return int Days, from 1 to RESUBMIT_EXPIRY_DAYS.
	 */
	public static function get_resubmit_expiry_days(): int {
		/**
		 * Filters the number of days after its last sync that a product is resubmitted to Merchant Center.
		 *
		 * The daily resubmission job picks up products last synced longer ago than this. The value
		 * is clamped to between 1 and 25, so products are always resubmitted before Google expires
		 * them 30 days after their last update. A non-numeric value falls back to the default.
		 *
		 * @since 3.11.0
		 *
		 * @param int $days Days after the last sync. Default 25.
		 */
		$days = apply_filters( 'woocommerce_gla_resubmit_expiry_days', self::RESUBMIT_EXPIRY_DAYS );

		if ( ! is_numeric( $days ) ) {
			return self::RESUBMIT_EXPIRY_DAYS;
		}

		return max( 1, min( (int) $days, self::RESUBMIT_EXPIRY_DAYS ) );
	}

	/**
	 * Find the IDs of products queued for a delayed update job, ordered by ID ascending.
	 *
	 * Note: Includes product variations.
	 *
	 * @param int $limit Maximum number of results to retrieve or -1 for unlimited.
	 *
	 * @return int[] Array of WooCommerce product IDs ordered by ID ASC.
	 */
	public function find_queued_update_ids( int $limit = -1 ): array {
		$args = [
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => [
				[
					'key'     => ProductMetaHandler::KEY_SYNC_QUEUED_AT,
					'compare' => 'EXISTS',
				],
			],
		];

		return $this->find_ids( $args, $limit );
	}

	/**
	 * Find all simple and variable product IDs regardless of MC status or visibility.
	 *
	 * @since 2.6.4
	 *
	 * @param int $limit  Maximum number of results to retrieve or -1 for unlimited.
	 * @param int $offset Amount to offset product results.
	 *
	 * @return int[] Array of WooCommerce product IDs
	 */
	public function find_all_product_ids( int $limit = -1, int $offset = 0 ): array {
		$args = [
			'status' => 'publish',
			'return' => 'ids',
			'type'   => 'any',
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

		$query_args = $this->prepare_query_args( $args );

		// WPML scopes every post query to the current language. The plugin manages
		// products across all languages (each translation is its own Merchant Center
		// entry), so run the query in the all-languages context. No-op without WPML.
		return $this->wpml->run_in_all_languages(
			static function () use ( $query_args ) {
				return wc_get_products( $query_args );
			}
		);
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

		// It'll fetch all products with the post_type of 'product', excluding variations.
		if ( $args['type'] === 'any' ) {
			unset( $args['type'] );
		}

		// use no ordering unless specified in arguments. overrides the default WooCommerce query args
		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'none';
		}

		/**
		 * Filters the product query args before they are passed to wc_get_products().
		 *
		 * For the expiring products lookup (find_expiring_product_ids()), `meta_query` holds only
		 * the synced_at clause. Its visibility, errors and publish status conditions are applied in
		 * SQL outside these args and cannot be changed through this filter.
		 *
		 * @param array $args Product query args.
		 */
		$args = apply_filters( 'woocommerce_gla_product_query_args', $args );

		return $args;
	}
}
