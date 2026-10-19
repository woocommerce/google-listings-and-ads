<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use WC_Data;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductMetaQueryHelper.
 *
 * @since 1.1.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class ProductMetaQueryHelper implements Service {

	use PluginHelper;

	protected const BATCH_SIZE = 500;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * ProductMetaQueryHelper constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get all values for a given meta_key as post_id=>meta_value.
	 *
	 * @param string $meta_key The meta value to retrieve for all posts.
	 * @return array Meta values by post ID.
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function get_all_values( string $meta_key ): array {
		self::validate_meta_key( $meta_key );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT post_id, meta_value FROM %i WHERE meta_key = %s',
				$this->wpdb->postmeta,
				$this->prefix_meta_key( $meta_key )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$return = [];
		foreach ( $results as $r ) {
			$return[ $r->post_id ] = maybe_unserialize( $r->meta_value );
		}
		return $return;
	}

	/**
	 * Delete all values for a given meta_key.
	 *
	 * @since 2.6.4
	 *
	 * @param string $meta_key The meta key to delete.
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function delete_all_values( string $meta_key ) {
		self::validate_meta_key( $meta_key );
		$meta_key = $this->prefix_meta_key( $meta_key );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$this->wpdb->query(
			$this->wpdb->prepare(
				'DELETE FROM %i WHERE meta_key = %s',
				$this->wpdb->postmeta,
				$meta_key
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Set one meta value on many posts with two queries, replacing any existing value.
	 *
	 * Writes the table directly: no meta hooks fire. The WordPress and WooCommerce meta
	 * caches and the posts query cache are cleared, but WooCommerce's per-request
	 * product instances already loaded keep their copy.
	 *
	 * @since x.x.x
	 *
	 * @param string $meta_key The meta key to set.
	 * @param int[]  $post_ids Posts to set it on.
	 * @param string $value    The meta value.
	 *
	 * @return bool False when a query failed (reported through `woocommerce_gla_error`).
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function set_value_for_posts( string $meta_key, array $post_ids, string $value ): bool {
		self::validate_meta_key( $meta_key );
		$post_ids = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );
		if ( empty( $post_ids ) ) {
			return true;
		}

		$success = true;

		$meta_key = $this->prefix_meta_key( $meta_key );

		foreach ( array_chunk( $post_ids, self::BATCH_SIZE ) as $chunk ) {
			$id_list = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$rows    = implode( ',', array_fill( 0, count( $chunk ), '(%d,%s,%s)' ) );
			$values  = [];
			foreach ( $chunk as $post_id ) {
				array_push( $values, $post_id, $meta_key, $value );
			}

			// The placeholder lists are generated locally from the chunk size.
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$deleted  = $this->wpdb->query(
				$this->wpdb->prepare( "DELETE FROM %i WHERE meta_key = %s AND post_id IN ({$id_list})", $this->wpdb->postmeta, $meta_key, ...$chunk )
			);
			$inserted = false !== $deleted && $this->wpdb->query(
				$this->wpdb->prepare( "INSERT INTO %i (post_id, meta_key, meta_value) VALUES {$rows}", $this->wpdb->postmeta, ...$values )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter

			if ( false === $inserted ) {
				$success = false;
				do_action(
					'woocommerce_gla_error',
					sprintf( 'Could not set the %s meta of %d posts: %s', $meta_key, count( $chunk ), $this->wpdb->last_error ),
					__METHOD__
				);
			}

			$this->clear_meta_cache( $chunk );
		}

		// Meta queries (e.g. WP_Query meta_query) are cached against this.
		wp_cache_set_posts_last_changed();

		return $success;
	}

	/**
	 * Delete a meta key from every post whose marker meta is missing or holds a number
	 * lower than the given one, e.g. the statuses a completed refresh run did not write.
	 * Values marked by a later run are never touched. Passing the meta key itself as
	 * the marker deletes the values lower than the given number.
	 *
	 * Runs a SELECT on the meta_key index, then deletes in chunks, so the cost
	 * follows the number of stale values rather than the catalog size.
	 *
	 * Writes the table directly: no meta hooks fire. The WordPress and WooCommerce meta
	 * caches and the posts query cache are cleared, but WooCommerce's per-request
	 * product instances already loaded keep their copy.
	 *
	 * @since x.x.x
	 *
	 * @param string $meta_key   The meta key to delete.
	 * @param string $marker_key The meta key holding the marker.
	 * @param int    $before     Posts whose marker is at least this keep their meta.
	 *
	 * @throws InvalidMeta If a meta key isn't valid.
	 */
	public function delete_values_marked_before( string $meta_key, string $marker_key, int $before ): void {
		self::validate_meta_key( $meta_key );
		self::validate_meta_key( $marker_key );

		$meta_key   = $this->prefix_meta_key( $meta_key );
		$marker_key = $this->prefix_meta_key( $marker_key );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $this->wpdb is the injected wpdb instance, the queries are prepared inline, and the IN-list placeholders are generated locally.
		$post_ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				'SELECT DISTINCT m.post_id FROM %i m
				LEFT JOIN %i marker ON marker.post_id = m.post_id AND marker.meta_key = %s
				WHERE m.meta_key = %s AND ( marker.meta_id IS NULL OR CAST( marker.meta_value AS UNSIGNED ) < %d )',
				$this->wpdb->postmeta,
				$this->wpdb->postmeta,
				$marker_key,
				$meta_key,
				$before
			)
		);

		$post_ids = array_map( 'intval', (array) $post_ids );
		foreach ( array_chunk( $post_ids, self::BATCH_SIZE ) as $chunk ) {
			$id_list = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );

			// The run-order check is repeated in the DELETE itself: a later run may have
			// stamped some of these posts since the SELECT.
			if ( $marker_key === $meta_key ) {
				$query = $this->wpdb->prepare(
					"DELETE FROM %i WHERE meta_key = %s AND post_id IN ({$id_list}) AND CAST( meta_value AS UNSIGNED ) < %d",
					$this->wpdb->postmeta,
					$meta_key,
					...array_merge( $chunk, [ $before ] )
				);
			} else {
				$query = $this->wpdb->prepare(
					"DELETE m FROM %i m
					LEFT JOIN %i marker ON marker.post_id = m.post_id AND marker.meta_key = %s
					WHERE m.meta_key = %s AND m.post_id IN ({$id_list})
						AND ( marker.meta_id IS NULL OR CAST( marker.meta_value AS UNSIGNED ) < %d )",
					$this->wpdb->postmeta,
					$this->wpdb->postmeta,
					$marker_key,
					$meta_key,
					...array_merge( $chunk, [ $before ] )
				);
			}
			$this->wpdb->query( $query ); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
			$this->clear_meta_cache( $chunk );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		if ( ! empty( $post_ids ) ) {
			wp_cache_set_posts_last_changed();
		}
	}

	/**
	 * Drop the meta caches of posts whose meta was written directly: WordPress's, and
	 * WooCommerce's own product meta cache, which WC_Data::get_meta() reads (it matters
	 * with a persistent object cache).
	 *
	 * @param int[] $post_ids
	 */
	protected function clear_meta_cache( array $post_ids ): void {
		wp_cache_delete_multiple( $post_ids, 'post_meta' );

		if ( ! class_exists( WC_Data::class ) || ! method_exists( WC_Data::class, 'generate_meta_cache_key' ) ) {
			return;
		}

		$keys = [];
		foreach ( $post_ids as $post_id ) {
			$keys[] = WC_Data::generate_meta_cache_key( $post_id, 'products' );
		}
		wp_cache_delete_multiple( $keys, 'products' );
	}

	/**
	 * @param string $meta_key The meta key to validate
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	protected static function validate_meta_key( string $meta_key ) {
		if ( ! ProductMetaHandler::is_meta_key_valid( $meta_key ) ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Product meta key is invalid: %s', $meta_key ),
				__METHOD__
			);

			throw InvalidMeta::invalid_key( $meta_key );
		}
	}
}
