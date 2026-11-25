<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantIssueTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class MerchantIssueTable extends Table {

	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	protected function get_install_query(): string {
		return "
CREATE TABLE `{$this->get_sql_safe_name()}` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) NOT NULL,
    `issue` varchar(200) NOT NULL,
    `code` varchar(100) NOT NULL,
    `severity` varchar(20) NOT NULL DEFAULT 'warning',
    `product` varchar(100) NOT NULL,
    `action` text NOT NULL,
    `action_url` varchar(1024) NOT NULL,
    `applicable_countries` text NOT NULL,
    `source` varchar(10) NOT NULL DEFAULT 'mc',
    `type` varchar(10) NOT NULL DEFAULT 'product',
    `created_at` datetime NOT NULL,
    PRIMARY KEY `id` (`id`)
) {$this->get_collation()};
";
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
		return 'merchant_issues';
	}

	/**
	 * Delete stale issue records.
	 *
	 * @param DateTime $created_before Delete all records created before this.
	 */
	public function delete_stale( DateTime $created_before ): void {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$this->wpdb->query(
			$this->wpdb->prepare(
				'DELETE FROM %i WHERE `created_at` < %s',
				$this->get_sql_safe_name(),
				$created_before->format( 'Y-m-d H:i:s' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete product issues for specific products and source.
	 *
	 * @param array  $products_ids Array of product IDs to delete issues for.
	 * @param string $source       The source of the issues. Default is 'mc'.
	 */
	public function delete_specific_product_issues( array $products_ids, string $source = 'mc' ): void {
		if ( empty( $products_ids ) ) {
			return;
		}

		$placeholder = '(' . implode( ',', array_fill( 0, count( $products_ids ), '%d' ) ) . ')';
		$query       = "DELETE FROM %i WHERE `product_id` IN {$placeholder} AND `source` = %s";
		$values      = [ $this->get_sql_safe_name(), ...$products_ids, $source ];

		// The IN-list placeholders are generated locally from the product ID count.
		$this->wpdb->query( $this->wpdb->prepare( $query, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Delete issue records by their row IDs.
	 *
	 * @since 3.9.3
	 *
	 * @param int[] $ids Row IDs to delete.
	 */
	public function delete_by_ids( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}

		$placeholder = '(' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
		$query       = "DELETE FROM %i WHERE `id` IN {$placeholder}";
		$values      = [ $this->get_sql_safe_name(), ...$ids ];

		// The IN-list placeholders are generated locally from the row ID count.
		$this->wpdb->query( $this->wpdb->prepare( $query, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Count the Merchant Center account issues with any of the given severities.
	 *
	 * @since x.x.x
	 *
	 * @param string[] $severities Severities to count.
	 *
	 * @return int|null The count, or null when the query fails.
	 */
	public function count_account_issues( array $severities ): ?int {
		if ( empty( $severities ) ) {
			return 0;
		}

		$placeholder = '(' . implode( ',', array_fill( 0, count( $severities ), '%s' ) ) . ')';
		$query       = "SELECT COUNT(*) FROM %i WHERE `type` = 'account' AND `source` = 'mc' AND `severity` IN {$placeholder}";
		$values      = [ $this->get_sql_safe_name(), ...$severities ];

		// The IN-list placeholders are generated locally from the severity count.
		return $this->to_count( $this->wpdb->get_var( $this->wpdb->prepare( $query, $values ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Count the products with at least one product issue from the given source.
	 *
	 * A variation is counted as its parent product, so a variable product counts once.
	 *
	 * @since x.x.x
	 *
	 * @param string   $source              Issue source, e.g. 'mc' or 'pre-sync'.
	 * @param string[] $excluded_severities Severities to leave out of the count.
	 *
	 * @return int|null The count, or null when the query fails.
	 */
	public function count_products_with_issues( string $source, array $excluded_severities = [] ): ?int {
		$query  = "SELECT COUNT( DISTINCT IF( p.`post_type` = 'product_variation' AND p.`post_parent` > 0, p.`post_parent`, i.`product_id` ) )
			FROM %i i LEFT JOIN %i p ON p.`ID` = i.`product_id`
			WHERE i.`type` = 'product' AND i.`source` = %s";
		$values = [ $this->get_sql_safe_name(), $this->wpdb->posts, $source ];

		if ( ! empty( $excluded_severities ) ) {
			$query .= ' AND i.`severity` NOT IN (' . implode( ',', array_fill( 0, count( $excluded_severities ), '%s' ) ) . ')';
			$values = [ ...$values, ...$excluded_severities ];
		}

		// The NOT IN-list placeholders are generated locally from the severity count.
		return $this->to_count( $this->wpdb->get_var( $this->wpdb->prepare( $query, $values ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get the codes of the stored account issues from the given source.
	 *
	 * @since x.x.x
	 *
	 * @param string $source Issue source, e.g. 'filter'.
	 *
	 * @return string[]|null The codes, or null when the query fails.
	 */
	public function get_account_issue_codes( string $source ): ?array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- $this->wpdb is the injected wpdb instance and the query is prepared inline.
		$codes = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT `code` FROM %i WHERE `type` = 'account' AND `source` = %s",
				$this->get_sql_safe_name(),
				$source
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		// get_col() returns an empty array when the query fails.
		return $this->wpdb->last_error || ! is_array( $codes ) ? null : array_map( 'strval', $codes );
	}

	/**
	 * Get the most common product issue codes from the given source, with the number of products having each.
	 *
	 * A variation is counted as its parent product. Codes are normalized before grouping: an
	 * `[n]` index suffix is dropped, and md5 fallback pre-sync codes share one
	 * `presync_error_other` bucket.
	 *
	 * @since x.x.x
	 *
	 * @param string   $source              Issue source, e.g. 'mc' or 'pre-sync'.
	 * @param string[] $excluded_severities Severities to leave out.
	 * @param int      $limit               Maximum number of codes to return.
	 *
	 * @return int[]|null Product counts keyed by code, most common first (ties by code), or null when the query fails.
	 */
	public function get_top_issue_codes( string $source, array $excluded_severities = [], int $limit = 5 ): ?array {
		// MerchantStatuses::parse_presync_issue_text() emits 'presync_error_attrib_' . md5( $text ) for unparsed text.
		// INSTR/SUBSTR rather than SUBSTRING_INDEX, which the SQLite integration (Studio, Playground) lacks.
		$query  = "SELECT CASE WHEN i.`code` REGEXP %s THEN 'presync_error_other' WHEN INSTR( i.`code`, '[' ) > 0 THEN SUBSTR( i.`code`, 1, INSTR( i.`code`, '[' ) - 1 ) ELSE i.`code` END AS issue_code,
				COUNT( DISTINCT IF( p.`post_type` = 'product_variation' AND p.`post_parent` > 0, p.`post_parent`, i.`product_id` ) ) AS product_count
			FROM %i i LEFT JOIN %i p ON p.`ID` = i.`product_id`
			WHERE i.`type` = 'product' AND i.`source` = %s";
		$values = [ '^presync_error_attrib_[0-9a-f]{32}$', $this->get_sql_safe_name(), $this->wpdb->posts, $source ];

		if ( ! empty( $excluded_severities ) ) {
			$query .= ' AND i.`severity` NOT IN (' . implode( ',', array_fill( 0, count( $excluded_severities ), '%s' ) ) . ')';
			$values = [ ...$values, ...$excluded_severities ];
		}

		$query   .= ' GROUP BY issue_code ORDER BY product_count DESC, issue_code ASC LIMIT %d';
		$values[] = $limit;

		// The NOT IN-list placeholders are generated locally from the severity count.
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $query, $values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// get_results() returns an empty array when the query fails.
		if ( $this->wpdb->last_error || ! is_array( $rows ) ) {
			return null;
		}

		$codes = [];
		foreach ( $rows as $row ) {
			$codes[ $row['issue_code'] ] = (int) $row['product_count'];
		}

		return $codes;
	}

	/**
	 * Convert a COUNT query result to an int, or null when the query failed (COUNT always returns a row).
	 *
	 * @param mixed $result The get_var() result.
	 *
	 * @return int|null
	 */
	private function to_count( $result ): ?int {
		return is_numeric( $result ) ? (int) $result : null;
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'id'                   => true,
			'product_id'           => true,
			'code'                 => true,
			'severity'             => true,
			'issue'                => true,
			'product'              => true,
			'action'               => true,
			'action_url'           => true,
			'applicable_countries' => true,
			'source'               => true,
			'type'                 => true,
			'created_at'           => true,
		];
	}
}
