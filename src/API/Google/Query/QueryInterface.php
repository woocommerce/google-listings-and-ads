<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Interface QueryInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
interface QueryInterface {

	/**
	 * Set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return $this
	 */
	public function columns( array $columns ): QueryInterface;

	/**
	 * Add a set columns to retrieve in the query.
	 *
	 * @param array $columns List of column names.
	 *
	 * @return $this
	 */
	public function add_columns( array $columns ): QueryInterface;

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
	 * Get the results of the query.
	 *
	 * @return mixed
	 */
	public function get_results();
}
