<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class ShippingTimeQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb              $wpdb
	 * @param ShippingTimeTable $table
	 */
	public function __construct( wpdb $wpdb, ShippingTimeTable $table ) {
		parent::__construct( $wpdb, $table );
	}

	/**
	 * Sanitize a value for a given column before inserting it into the DB.
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The value to sanitize.
	 *
	 * @return mixed The sanitized value.
	 * @throws InvalidQuery When the code tries to set the ID column.
	 */
	protected function sanitize_value( string $column, $value ) {
		if ( 'id' === $column ) {
			throw InvalidQuery::cant_set_id( ShippingTimeTable::class );
		}

		return $value;
	}
}
