<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use DateTime;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantIssueQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class MerchantIssueQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb               $wpdb
	 * @param MerchantIssueTable $table
	 */
	public function __construct( wpdb $wpdb, MerchantIssueTable $table ) {
		parent::__construct( $wpdb, $table );
	}

	/**
	 * Sanitize a value for a given column before inserting it into the DB.
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The value to sanitize.
	 *
	 * @return mixed The sanitized value.
	 */
	protected function sanitize_value( string $column, $value ) {
		return $value;
	}

	/**
	 * Delete stale issue records.
	 *
	 * @param DateTime $created_before Delete all records created before this.
	 */
	public function delete_stale( DateTime $created_before ) {
		$query = 'DELETE FROM ' . $this->table->get_sql_safe_name() . ' WHERE `created_at` < \'%s\'';
		$this->wpdb->query( $this->wpdb->prepare( $query, $created_before->format( 'Y-m-d H:i:s' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
