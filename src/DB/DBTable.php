<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
abstract class DBTable implements DBTableInterface {

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
		// TODO: Implement install() method.
	}

	/**
	 * Delete the Database table.
	 */
	public function delete(): void {
		$this->wpdb->query( "DROP TABLE `{$this->get_table_name()}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	abstract protected function get_db_schema(): string;

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	abstract protected function get_table_name(): string;
}
