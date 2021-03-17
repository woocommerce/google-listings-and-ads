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
	 * Query constructor.
	 *
	 * @param string $resource
	 */
	public function __construct( string $resource ) {
		$this->resource = $resource;
	}

	/**
	 * Set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return $this
	 */
	public function columns( array $columns ): QueryInterface {
		$this->columns = $columns;

		return $this;
	}

	/**
	 * Add a set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return $this
	 */
	public function add_columns( array $columns ): QueryInterface {
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
	 * @return $this
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
	 * Get the built query.
	 *
	 * @return string
	 */
	public function get_query(): string {
		return $this->build_query();
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

		return add_slashes( (string) $value );
	}
}
