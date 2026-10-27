<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 *
 * @see TableManager::VALID_TABLES contains a list of table classes that will be installed.
 * @see \Automattic\WooCommerce\GoogleListingsAndAds\DB\Installer::install for installing tables.
 */
abstract class Table implements TableInterface {

	use PluginHelper;

	/** @var WP */
	protected $wp;

	/** @var wpdb */
	protected $wpdb;

	/**
	 * Table constructor.
	 *
	 * @param WP   $wp   The WP proxy object.
	 * @param wpdb $wpdb The wpdb object.
	 */
	public function __construct( WP $wp, wpdb $wpdb ) {
		$this->wp   = $wp;
		$this->wpdb = $wpdb;
	}

	/**
	 * Install the Database table.
	 */
	public function install(): void {
		$this->wp->db_delta( $this->get_install_query() );
	}

	/**
	 * Determine whether the table actually exists in the DB.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->wpdb->esc_like( $this->get_name() )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return $result === $this->get_name();
	}

	/**
	 * Delete the Database table.
	 */
	public function delete(): void {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$this->wpdb->query(
			$this->wpdb->prepare( 'DROP TABLE IF EXISTS %i', $this->get_sql_safe_name() )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Truncate the Database table.
	 */
	public function truncate(): void {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$this->wpdb->query(
			$this->wpdb->prepare( 'TRUNCATE TABLE %i', $this->get_sql_safe_name() )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get the SQL escaped version of the table name.
	 *
	 * @return string
	 */
	protected function get_sql_safe_name(): string {
		return $this->wpdb->_escape( $this->get_name() );
	}

	/**
	 * Get the name of the Database table.
	 *
	 * The name is prefixed with the wpdb prefix, and our plugin prefix.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return "{$this->wpdb->prefix}{$this->get_slug()}_{$this->get_raw_name()}";
	}

	/**
	 * Get the primary column name for the table.
	 *
	 * @return string
	 */
	public function get_primary_column(): string {
		return 'id';
	}

	/**
	 * Checks whether an index exists for the table.
	 *
	 * @param string $index_name The index name.
	 *
	 * @return bool True if the index exists on the table and False if not.
	 *
	 * @since 1.4.1
	 */
	public function has_index( string $index_name ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$result = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SHOW INDEX FROM %i WHERE Key_name = %s',
				$this->get_sql_safe_name(),
				$index_name
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return ! empty( $result );
	}

	/**
	 * Get the DB collation.
	 *
	 * @return string
	 */
	protected function get_collation(): string {
		return $this->wpdb->has_cap( 'collation' ) ? $this->wpdb->get_charset_collate() : '';
	}

	/**
	 * Checks whether a column exists for the table.
	 *
	 * @param string $column_name The column name.
	 *
	 * @return bool True if the column exists on the table or False if not.
	 *
	 * @since 2.5.13
	 */
	public function has_column( string $column_name ): bool {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$this->wpdb->get_results(
			$this->wpdb->prepare(
				'SHOW COLUMNS FROM %i WHERE Field = %s',
				$this->get_sql_safe_name(),
				$column_name
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		return (bool) $this->wpdb->num_rows;
	}


	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	abstract protected function get_install_query(): string;

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	abstract public static function get_raw_name(): string;
}
