<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
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
	 * Count the top-level products whose meta value for a given meta_key is one of the given values.
	 *
	 * Variations are not counted; a variable product counts once through its parent.
	 *
	 * @since x.x.x
	 *
	 * @param string   $meta_key The meta key to match.
	 * @param string[] $values   The meta values to count.
	 *
	 * @return int
	 *
	 * @throws InvalidMeta If the meta key isn't valid.
	 */
	public function count_products_by_value( string $meta_key, array $values ): int {
		self::validate_meta_key( $meta_key );

		if ( empty( $values ) ) {
			return 0;
		}

		$placeholder = '(' . implode( ',', array_fill( 0, count( $values ), '%s' ) ) . ')';
		$query       = "SELECT COUNT( DISTINCT m.post_id ) FROM %i m INNER JOIN %i p ON p.ID = m.post_id
			WHERE p.post_type = 'product' AND m.meta_key = %s AND m.meta_value IN {$placeholder}";
		$args        = [ $this->wpdb->postmeta, $this->wpdb->posts, $this->prefix_meta_key( $meta_key ), ...$values ];

		// The IN-list placeholders are generated locally from the value count.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = $this->wpdb->get_var( $this->wpdb->prepare( $query, $args ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return (int) $count;
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
