<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Class Query
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
abstract class Query implements QueryInterface {

	/** @var string */
	protected $order = 'ASC';

	/** @var string */
	protected $orderby;

	/**
	 * The result of the query.
	 *
	 * @var mixed
	 */
	protected $results = null;

	/** @var TableInterface */
	protected $table;

	/**
	 * Where clauses for the query.
	 *
	 * @var array
	 */
	protected $where = [];

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
	 * Set ordering information for the query.
	 *
	 * @param string $column
	 * @param string $order
	 *
	 * @return QueryInterface
	 */
	public function set_order( string $column, string $order = 'ASC' ): QueryInterface {
		$this->validate_column( $column );
		$this->orderby = $column;
		$this->order   = $this->normalize_order( $order );

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
	 * Perform the query and save it to the results.
	 */
	protected function query_results() {

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
}
