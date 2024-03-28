<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
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

		$query = "SELECT post_id, meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $this->prefix_meta_key( $meta_key ) ) );
		$return  = [];
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
		$query    = "DELETE FROM {$this->wpdb->postmeta} WHERE meta_key = %s";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->wpdb->query( $this->wpdb->prepare( $query, $meta_key ) );
	}

	/**
	 * Insert a meta value for multiple posts.
	 *
	 * @param string $meta_key The meta value to insert.
	 * @param array  $meta_values Array of [post_id=>meta_value,…].
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function batch_insert_values( string $meta_key, array $meta_values ) {
		if ( empty( $meta_values ) ) {
			return;
		}

		self::validate_meta_key( $meta_key );
		$meta_key = $this->prefix_meta_key( $meta_key );

		foreach ( array_chunk( $meta_values, self::BATCH_SIZE, true ) as $meta_values_chunk ) {
			$values        = [];
			$place_holders = [];
			foreach ( $meta_values_chunk as $post_id => $meta_value ) {
				$place_holders[] = '(%s, %s, %s)';
				$values[]        = $meta_key;
				$values[]        = $post_id;
				$values[]        = $meta_value;
			}
			$query  = "INSERT INTO {$this->wpdb->postmeta} (`meta_key`, `post_id`, `meta_value` ) VALUES ";
			$query .= implode( ', ', $place_holders );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $this->wpdb->query( $this->wpdb->prepare( $query, $values ) ) ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Error batch inserting "%s" meta values.', $meta_key ),
					__METHOD__
				);
			}
		}
	}

	/**
	 * Update a meta value for multiple posts.
	 *
	 * @param string $meta_key The meta value to update.
	 * @param mixed  $meta_value The new meta value.
	 * @param array  $post_ids Post IDs to update.
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function batch_update_values( string $meta_key, $meta_value, array $post_ids ) {
		if ( empty( $post_ids ) ) {
			return;
		}

		self::validate_meta_key( $meta_key );
		$meta_key = $this->prefix_meta_key( $meta_key );

		foreach ( array_chunk( $post_ids, self::BATCH_SIZE ) as $post_ids_chunk ) {
			$query = "UPDATE {$this->wpdb->postmeta} SET `meta_value` = %s WHERE `meta_key` = %s AND `post_id` IN (%d" . str_repeat( ', %d', count( $post_ids_chunk ) - 1 ) . ')';
			array_unshift( $post_ids_chunk, $meta_value, $meta_key );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $this->wpdb->query( $this->wpdb->prepare( $query, $post_ids_chunk ) ) ) {
				do_action(
					'woocommerce_gla_debug_message',
					sprintf( 'Error batch updating "%s" meta values.', $meta_key ),
					__METHOD__
				);
			}
		}
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
