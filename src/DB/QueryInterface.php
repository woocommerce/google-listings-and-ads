<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

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
}
