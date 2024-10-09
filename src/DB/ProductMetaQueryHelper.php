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
