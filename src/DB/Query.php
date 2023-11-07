<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\PositiveInteger;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Query
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
abstract class Query implements QueryInterface {

	/** @var int */
	protected $limit;

	/** @var int */
	protected $offset = 0;

	/** @var array */
	protected $orderby = [];

	/** @var array */
	protected $groupby = [];

	/**
	 * The result of the query.
	 *
	 * @var mixed
	 */
	protected $results = null;

	/**
	 * The number of rows returned by the query.
	 *
	 * @var int
	 */
	protected $count = null;

	/**
	 * The last inserted ID (updated after a call to insert).
	 *
	 * @var int
	 */
	protected $last_insert_id = null;

	/** @var TableInterface */
	protected $table;

	/**
	 * Where clauses for the query.
	 *
	 * @var array
	 */
	protected $where = [];

	/**
	 * Where relation for multiple clauses.
	 *
	 * @var string
	 */
	protected $where_relation;

	/** @var wpdb */
	protected $wpdb;

	/**
	 * Query constructor.
	 *
	 * @param wpdb           $wpdb
	 * @param TableInterface $table
	 */
	public function __construct( wpdb $wpdb, TableInterface $table ) {
		$this->wpdb  = $wpdb;
		$this->table = $table;
	}

	/**
	 * Add a where clause to the query.
	 *
	 * @param string $column  The column name.
	 * @param mixed  $value   The where value.
	 * @param string $compare The comparison to use. Valid values are =, <, >, IN, NOT IN.
	 *
	 * @return $this
	 */
	public function where( string $column, $value, string $compare = '=' ): QueryInterface {
		$this->validate_column( $column );
		$this->validate_compare( $compare );
		$this->where[] = [
			'column'  => $column,
			'value'   => $value,
			'compare' => $compare,
		];

		return $this;
	}

	/**
	 * Add a group by clause to the query.
	 *
	 * @param string $column  The column name.
	 *
	 * @return $this
	 *
	 * @since 1.12.0
	 */
	public function group_by( string $column ): QueryInterface {
		$this->validate_column( $column );
		$this->groupby[] = "`{$column}`";

		return $this;
	}

	/**
	 * Set the where relation for the query.
	 *
	 * @param string $relation
	 *
	 * @return QueryInterface
	 */
	public function set_where_relation( string $relation ): QueryInterface {
		$this->validate_where_relation( $relation );
		$this->where_relation = $relation;

		return $this;
	}

	/**
	 * Set ordering information for the query.
	 *
	 * @param string $column
	 * @param string $order
	 *
	 * @return QueryInterface
	 */
	public function set_order( string $column, string $order = 'ASC' ): QueryInterface {
		$this->validate_column( $column );
		$this->orderby[] = "`{$column}` {$this->normalize_order( $order )}";
		return $this;
	}

	/**
	 * Limit the number of results for the query.
	 *
	 * @param int $limit
	 *
	 * @return QueryInterface
	 */
	public function set_limit( int $limit ): QueryInterface {
		$this->limit = ( new PositiveInteger( $limit ) )->get();

		return $this;
	}

	/**
	 * Set an offset for the results.
	 *
	 * @param int $offset
	 *
	 * @return QueryInterface
	 */
	public function set_offset( int $offset ): QueryInterface {
		$this->offset = ( new PositiveInteger( $offset ) )->get();

		return $this;
	}

	/**
	 * Get the results of the query.
	 *
	 * @return mixed
	 */
	public function get_results() {
		if ( null === $this->results ) {
			$this->query_results();
		}

		return $this->results;
	}

	/**
	 * Get the number of results returned by the query.
	 *
	 * @return int
	 */
	public function get_count(): int {
		if ( null === $this->count ) {
			$this->count_results();
		}

		return $this->count;
	}

	/**
	 * Gets the first result of the query.
	 *
	 * @return array
	 */
	public function get_row(): array {
		if ( null === $this->results ) {
			$old_limit = $this->limit ?? 0;
			$this->set_limit( 1 );
			$this->query_results();
			$this->set_limit( $old_limit );
		}

		return $this->results[0] ?? [];
	}

	/**
	 * Perform the query and save it to the results.
	 */
	protected function query_results() {
		$this->results = $this->wpdb->get_results(
			$this->build_query(), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
	}

	/**
	 * Count the results and save the result.
	 */
	protected function count_results() {
		$this->count = (int) $this->wpdb->get_var( $this->build_query( true ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Validate that a given column is valid for the current table.
	 *
	 * @param string $column
	 *
	 * @throws InvalidQuery When the given column is not valid for the current table.
	 */
	protected function validate_column( string $column ) {
		if ( ! array_key_exists( $column, $this->table->get_columns() ) ) {
			throw InvalidQuery::from_column( $column, get_class( $this->table ) );
		}
	}

	/**
	 * Validate that a compare operator is valid.
	 *
	 * @param string $compare
	 *
	 * @throws InvalidQuery When the compare value is not valid.
	 */
	protected function validate_compare( string $compare ) {
		switch ( $compare ) {
			case '=':
			case '>':
			case '<':
			case 'IN':
			case 'NOT IN':
				// These are all valid.
				return;

			default:
				throw InvalidQuery::from_compare( $compare );
		}
	}


	/**
	 * Validate that a where relation is valid.
	 *
	 * @param string $relation
	 *
	 * @throws InvalidQuery When the relation value is not valid.
	 */
	protected function validate_where_relation( string $relation ) {
		switch ( $relation ) {
			case 'AND':
			case 'OR':
				// These are all valid.
				return;

			default:
				throw InvalidQuery::where_relation( $relation );
		}
	}

	/**
	 * Normalize the string for the order.
	 *
	 * Converts the string to uppercase, and will return only DESC or ASC.
	 *
	 * @param string $order
	 *
	 * @return string
	 */
	protected function normalize_order( string $order ): string {
		$order = strtoupper( $order );

		return 'DESC' === $order ? $order : 'ASC';
	}

	/**
	 * Build the query and return the query string.
	 *
	 * @param bool $get_count False to build a normal query, true to build a COUNT(*) query.
	 *
	 * @return string
	 */
	protected function build_query( bool $get_count = false ): string {
		$columns = $get_count ? 'COUNT(*)' : '*';
		$pieces  = [ "SELECT {$columns} FROM `{$this->table->get_name()}`" ];

		$pieces = array_merge( $pieces, $this->generate_where_pieces() );

		if ( ! empty( $this->groupby ) ) {
			$pieces[] = 'GROUP BY ' . implode( ', ', $this->groupby );
		}

		if ( ! $get_count ) {
			if ( empty( $this->groupby ) ) {
				$pieces[] = "GROUP BY `{$this->table->get_name()}`.`{$this->table->get_primary_column()}`";
			}

			if ( $this->orderby ) {
				$pieces[] = 'ORDER BY ' . implode( ', ', $this->orderby );
			}

			if ( $this->limit ) {
				$pieces[] = "LIMIT {$this->limit}";
			}

			if ( $this->offset ) {
				$pieces[] = "OFFSET {$this->offset}";
			}
		}

		return join( "\n", $pieces );
	}

	/**
	 * Generate the pieces for the WHERE part of the query.
	 *
	 * @return string[]
	 */
	protected function generate_where_pieces(): array {
		if ( empty( $this->where ) ) {
			return [];
		}

		$where_pieces = [ 'WHERE' ];
		foreach ( $this->where as $where ) {
			$column  = $where['column'];
			$compare = $where['compare'];

			if ( $compare === 'IN' || $compare === 'NOT IN' ) {
				$value = sprintf(
					"('%s')",
					join(
						"','",
						array_map(
							function ( $value ) {
								return $this->wpdb->_escape( $value );
							},
							$where['value']
						)
					)
				);
			} else {
				$value = "'{$this->wpdb->_escape( $where['value'] )}'";
			}

			if ( count( $where_pieces ) > 1 ) {
				$where_pieces[] = $this->where_relation ?? 'AND';
			}

			$where_pieces[] = "{$column} {$compare} {$value}";
		}

		return $where_pieces;
	}

	/**
	 * Insert a row of data into the table.
	 *
	 * @param array $data
	 *
	 * @return int
	 * @throws InvalidQuery When there is an error inserting the data.
	 */
	public function insert( array $data ): int {
		foreach ( $data as $column => &$value ) {
			$this->validate_column( $column );
			$value = $this->sanitize_value( $column, $value );
		}

		$result = $this->wpdb->insert( $this->table->get_name(), $data );

		if ( false === $result ) {
			throw InvalidQuery::from_insert( $this->wpdb->last_error ?: 'Error inserting data.' );
		}

		// Save a local copy of the last inserted ID.
		$this->last_insert_id = $this->wpdb->insert_id;

		return $result;
	}

	/**
	 * Returns the last inserted ID. Must be called after insert.
	 *
	 * @since 1.12.0
	 *
	 * @return int|null
	 */
	public function last_insert_id(): ?int {
		return $this->last_insert_id;
	}

	/**
	 * Delete rows from the database.
	 *
	 * @param string $where_column Column to use when looking for values to delete.
	 * @param mixed  $value        Value to use when determining what rows to delete.
	 *
	 * @return int The number of rows deleted.
	 * @throws InvalidQuery When there is an error deleting data.
	 */
	public function delete( string $where_column, $value ): int {
		$this->validate_column( $where_column );
		$result = $this->wpdb->delete( $this->table->get_name(), [ $where_column => $value ] );

		if ( false === $result ) {
			throw InvalidQuery::from_delete( $this->wpdb->last_error ?: 'Error deleting data.' );
		}

		return $result;
	}

	/**
	 * Update data in the database.
	 *
	 * @param array $data  Array of columns and their values.
	 * @param array $where Array of where conditions for updating values.
	 *
	 * @return int
	 * @throws InvalidQuery When there is an error updating data, or when an empty where array is provided.
	 */
	public function update( array $data, array $where ): int {
		if ( empty( $where ) ) {
			throw InvalidQuery::empty_where();
		}

		foreach ( $data as $column => &$value ) {
			$this->validate_column( $column );
			$value = $this->sanitize_value( $column, $value );
		}

		$result = $this->wpdb->update(
			$this->table->get_name(),
			$data,
			$where
		);

		if ( false === $result ) {
			throw InvalidQuery::from_update( $this->wpdb->last_error ?: 'Error updating data.' );
		}

		return $result;
	}

	/**
	 * Batch update or insert a set of records.
	 *
	 * @param array $records Array of records to be updated or inserted.
	 *
	 * @throws InvalidQuery If an invalid column name is provided.
	 */
	public function update_or_insert( array $records ): void {
		if ( empty( $records ) ) {
			return;
		}

		$update_values = [];
		$columns       = array_keys( reset( $records ) );
		foreach ( $columns as $c ) {
			$this->validate_column( $c );
			$update_values[] = "`$c`=VALUES(`$c`)";
		}

		$single_placeholder = '(' . implode( ',', array_fill( 0, count( $columns ), "'%s'" ) ) . ')';
		$chunk_size         = 200;
		$num_issues         = count( $records );
		for ( $i = 0; $i < $num_issues; $i += $chunk_size ) {
			$all_values       = [];
			$all_placeholders = [];
			foreach ( array_slice( $records, $i, $chunk_size ) as $issue ) {
				if ( array_keys( $issue ) !== $columns ) {
					throw new InvalidQuery( 'Not all records contain the same columns' );
				}
				$all_placeholders[] = $single_placeholder;
				array_push( $all_values, ...array_values( $issue ) );
			}

			$column_names = '(`' . implode( '`, `', $columns ) . '`)';

			$query  = "INSERT INTO `{$this->table->get_name()}` $column_names VALUES ";
			$query .= implode( ', ', $all_placeholders );
			$query .= ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $update_values );

			$this->wpdb->query( $this->wpdb->prepare( $query, $all_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
	}

	/**
	 * Sanitize a value for a given column before inserting it into the DB.
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The value to sanitize.
	 *
	 * @return mixed The sanitized value.
	 */
	abstract protected function sanitize_value( string $column, $value );
}
