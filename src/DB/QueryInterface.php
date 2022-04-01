<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Interface QueryInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
interface QueryInterface {

	/**
	 * Set a where clause to query.
	 *
	 * @param string $column  The column name.
	 * @param mixed  $value   The where value.
	 * @param string $compare The comparison to use. Valid values are =, <, >, IN, NOT IN.
	 *
	 * @return $this
	 */
	public function where( string $column, $value, string $compare = '=' ): QueryInterface;

	/**
	 * Set the where relation for the query.
	 *
	 * @param string $relation
	 *
	 * @return QueryInterface
	 */
	public function set_where_relation( string $relation ): QueryInterface;

	/**
	 * @param string $column
	 * @param string $order
	 *
	 * @return QueryInterface
	 */
	public function set_order( string $column, string $order = 'DESC' ): QueryInterface;

	/**
	 * Limit the number of results for the query.
	 *
	 * @param int $limit
	 *
	 * @return QueryInterface
	 */
	public function set_limit( int $limit ): QueryInterface;

	/**
	 * Set an offset for the results.
	 *
	 * @param int $offset
	 *
	 * @return QueryInterface
	 */
	public function set_offset( int $offset ): QueryInterface;

	/**
	 * Get the results of the query.
	 *
	 * @return mixed
	 */
	public function get_results();

	/**
	 * Get the number of results returned by the query.
	 *
	 * @return int
	 */
	public function get_count(): int;

	/**
	 * Gets the first result of the query.
	 *
	 * @return array
	 */
	public function get_row(): array;

	/**
	 * Insert a row of data into the table.
	 *
	 * @param array $data
	 *
	 * @return int
	 * @throws InvalidQuery When there is an error inserting the data.
	 */
	public function insert( array $data ): int;

	/**
	 * Returns the last inserted ID. Must be called after insert.
	 *
	 * @since 1.12.0
	 *
	 * @return int|null
	 */
	public function last_insert_id(): ?int;

	/**
	 * Delete rows from the database.
	 *
	 * @param string $where_column Column to use when looking for values to delete.
	 * @param mixed  $value        Value to use when determining what rows to delete.
	 *
	 * @return int The number of rows deleted.
	 * @throws InvalidQuery When there is an error deleting data.
	 */
	public function delete( string $where_column, $value ): int;

	/**
	 * Update data in the database.
	 *
	 * @param array $data  Array of columns and their values.
	 * @param array $where Array of where conditions for updating values.
	 *
	 * @return int
	 * @throws InvalidQuery When there is an error updating data, or when an empty where array is provided.
	 */
	public function update( array $data, array $where ): int;

	/**
	 * Batch update or insert a set of records.
	 *
	 * @param array $records Array of records to be updated or inserted.
	 *
	 * @throws InvalidQuery If an invalid column name is provided.
	 */
	public function update_or_insert( array $records ): void;
}
