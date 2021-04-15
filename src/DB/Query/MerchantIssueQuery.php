<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
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
	 * Batch update or insert a set of merchant issues.
	 *
	 * @param array $issues The issues to be updated or inserted.
	 */
	public function update_or_insert( array $issues ) {
		if ( empty( $issues ) ) {
			return;
		}

		$update_values = [];
		$columns       = array_keys( reset( $issues ) );
		foreach ( $columns as $c ) {
			$update_values[] = "`$c`=VALUES(`$c`)";
		}
		$single_placeholder = '(' . implode( ',', array_fill( 0, count( $columns ), "'%s'" ) ) . ')';

		$all_values       = [];
		$all_placeholders = [];
		foreach ( $issues as $i ) {
			$all_placeholders[] = $single_placeholder;
			array_push( $all_values, ...array_values( $i ) );
		}

		$query  = 'INSERT INTO ' . $this->table->get_sql_safe_name() . ' (`' . implode( '`, `', $columns ) . '`) VALUES ';
		$query .= implode( ', ', $all_placeholders );
		$query .= ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $update_values );

		$this->wpdb->query( $this->wpdb->prepare( $query, $all_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL
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
