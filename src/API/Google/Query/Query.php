<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Google Ads Query Language (GAQL)
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
abstract class Query implements QueryInterface {

	/**
	 * Resource name.
	 *
	 * @var string
	 */
	protected $resource;

	/**
	 * Set of columns to retrieve in the query.
	 *
	 * @var array
	 */
	protected $columns = [];

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

	/**
	 * Order sort attribute.
	 *
	 * @var string
	 */
	protected $order = 'ASC';

	/**
	 * Column to order by.
	 *
	 * @var string
	 */
	protected $orderby;

	/**
	 * The result of the query.
	 *
	 * @var mixed
	 */
	protected $results = null;

	/**
	 * Query constructor.
	 *
	 * @param string $resource
	 *
	 * @throws InvalidQuery When the resource name is not valid.
	 */
	public function __construct( string $resource ) {
		if ( ! preg_match( '/^[a-zA-Z_]+$/', $resource ) ) {
			throw InvalidQuery::resource_name();
		}

		$this->resource = $resource;
	}

	/**
	 * Set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return QueryInterface
	 */
	public function columns( array $columns ): QueryInterface {
		$this->validate_columns( $columns );
		$this->columns = $columns;

		return $this;
	}

	/**
	 * Add a set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return QueryInterface
	 */
	public function add_columns( array $columns ): QueryInterface {
		$this->validate_columns( $columns );
		$this->columns = array_merge( $this->columns, array_filter( $columns ) );

		return $this;
	}

	/**
	 * Add a where clause to the query.
	 *
	 * @param string $column  The column name.
	 * @param mixed  $value   The where value.
	 * @param string $compare The comparison to use. Valid values are =, <, >, IN, NOT IN.
	 *
	 * @return QueryInterface
	 */
	public function where( string $column, $value, string $compare = '=' ): QueryInterface {
		$this->validate_compare( $compare );
		$this->where[] = [
			'column'  => $column,
			'value'   => $value,
			'compare' => $compare,
		];

		return $this;
	}

	/**
	 * Add a where date between clause to the query.
	 *
	 * @since 1.7.0
	 *
	 * @link https://developers.google.com/shopping-content/guides/reports/query-language/date-ranges
	 *
	 * @param string $after  Start of date range. In ISO 8601(YYYY-MM-DD) format.
	 * @param string $before End of date range. In ISO 8601(YYYY-MM-DD) format.
	 *
	 * @return QueryInterface
	 */
	public function where_date_between( string $after, string $before ): QueryInterface {
		return $this->where( 'segments.date', [ $after, $before ], 'BETWEEN' );
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
	 * @throws InvalidQuery When the given column is not in the list of included columns.
	 */
	public function set_order( string $column, string $order = 'ASC' ): QueryInterface {
		if ( ! array_key_exists( $column, $this->columns ) ) {
			throw InvalidQuery::invalid_order_column( $column );
		}

		$this->orderby = $this->columns[ $column ];
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
		$this->results = [];
	}

	/**
	 * Validate a set of columns.
	 *
	 * @param array $columns
	 *
	 * @throws InvalidQuery When one of columns in the set is not valid.
	 */
	protected function validate_columns( array $columns ) {
		array_walk( $columns, [ $this, 'validate_column' ] );
	}

	/**
	 * Validate that a given column is using a valid name.
	 *
	 * @param string $column
	 *
	 * @throws InvalidQuery When the given column is not valid.
	 */
	protected function validate_column( string $column ) {
		if ( ! preg_match( '/^[a-zA-Z0-9\._]+$/', $column ) ) {
			throw InvalidQuery::invalid_column( $column );
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
			case '!=':
			case 'IN':
			case 'NOT IN':
			case 'BETWEEN':
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
	 * @return string
	 *
	 * @throws InvalidQuery When the set of columns is empty.
	 */
	protected function build_query(): string {
		if ( empty( $this->columns ) ) {
			throw InvalidQuery::empty_columns();
		}

		$columns = join( ',', $this->columns );
		$pieces  = [ "SELECT {$columns} FROM {$this->resource}" ];
		$pieces  = array_merge( $pieces, $this->generate_where_pieces() );

		if ( $this->orderby ) {
			$pieces[] = "ORDER BY {$this->orderby} {$this->order}";
		}

		return join( ' ', $pieces );
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

			if ( 'IN' === $compare || 'NOT_IN' === $compare ) {
				$value = sprintf(
					"('%s')",
					join(
						"','",
						array_map(
							function( $value ) {
								return $this->escape( $value );
							},
							$where['value']
						)
					)
				);
			} elseif ( 'BETWEEN' === $compare ) {
				$value = "'{$this->escape( $where['value'][0] )}' AND '{$this->escape( $where['value'][1] )}'";
			} else {
				$value = "'{$this->escape( $where['value'] )}'";
			}

			if ( count( $where_pieces ) > 1 ) {
				$where_pieces[] = $this->where_relation ?? 'AND';
			}

			$where_pieces[] = "{$column} {$compare} {$value}";
		}

		return $where_pieces;
	}

	/**
	 * Escape the value to a string which can be used in a query.
	 *
	 * @param mixed $value Original value to escape.
	 *
	 * @return string
	 */
	protected function escape( $value ): string {
		if ( $value instanceof DateTime ) {
			return $value->format( 'Y-m-d' );
		}

		if ( ! is_numeric( $value ) ) {
			return (string) $value;
		}

		return addslashes( (string) $value );
	}
}
