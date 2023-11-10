<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidQuery;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class ShippingRateQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb              $wpdb
	 * @param ShippingRateTable $table
	 */
	public function __construct( wpdb $wpdb, ShippingRateTable $table ) {
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
			throw InvalidQuery::cant_set_id( ShippingRateTable::class );
		}

		if ( 'options' === $column ) {
			if ( ! is_array( $value ) ) {
				throw InvalidQuery::invalid_value( $column );
			}

			$value = json_encode( $value );
		}

		return $value;
	}

	/**
	 * Perform the query and save it to the results.
	 */
	protected function query_results() {
		parent::query_results();

		$this->results = array_map(
			function ( $row ) {
				$row['options'] = ! empty( $row['options'] ) ? json_decode( $row['options'], true ) : $row['options'];

				return $row;
			},
			$this->results
		);
	}
}
