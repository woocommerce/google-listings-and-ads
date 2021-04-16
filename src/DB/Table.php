<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
abstract class Table implements TableInterface {

	use PluginHelper;

	/** @var WP */
	protected $wp;

	/** @var wpdb */
	protected $wpdb;

	/**
	 * DBTable constructor.
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
		$result = $this->wpdb->get_var(
			"SHOW TABLES LIKE '{$this->get_sql_safe_name()}'" // phpcs:ignore WordPress.DB.PreparedSQL
		);

		return $result === $this->get_name();
	}

	/**
	 * Delete the Database table.
	 */
	public function delete(): void {
		$this->wpdb->query( "DROP TABLE `{$this->get_sql_safe_name()}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Truncate the Database table.
	 */
	public function truncate(): void {
		$this->wpdb->query( "TRUNCATE TABLE `{$this->get_sql_safe_name()}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
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
	 * Get the DB collation.
	 *
	 * @return string
	 */
	protected function get_collation(): string {
		return $this->wpdb->has_cap( 'collation' ) ? $this->wpdb->get_charset_collate() : '';
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
	abstract protected function get_raw_name(): string;
}
