<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 *
 * @since x.x.x
 */
class DBHelper implements Service {
	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * AbstractMigration constructor.
	 *
	 * @param wpdb $wpdb The wpdb object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Checks whether an index exists for the given database table.
	 *
	 * @param string $table_name The table's name.
	 * @param string $index_name The index name.
	 *
	 * @return bool True if the index exists on the table and False if not.
	 */
	public function index_exists( string $table_name, string $index_name ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return ! empty( $this->wpdb->get_results( $this->wpdb->prepare( "SHOW INDEX FROM `{$this->wpdb->_escape( $table_name )}` WHERE Key_name = %s ", [ $index_name ] ) ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
}
