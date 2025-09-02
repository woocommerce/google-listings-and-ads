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
	 * ShippingTimeQuery constructor.
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

	/**
	 * Retrieves all available shipping times and organizes them by country.
	 *
	 * Fetches all shipping time data using `get_results()`, processes it,
	 * and returns an associative array in a format suitable for JSON responses.
	 *
	 * @since 2.8.0
	 *
	 * @return array Associative array of shipping times indexed by country codes.
	 *               Each entry contains:
	 *               - `country_code` (string): The country code.
	 *               - `time` (string): The minimum shipping time.
	 *               - `max_time` (string): The maximum shipping time.
	 */
	public function get_all_shipping_times() {
		$times = $this->get_results();
		$items = [];
		foreach ( $times as $time ) {
			$data = [
				'country_code' => $time['country'],
				'time'         => $time['time'],
				'max_time'     => $time['max_time'] ?: $time['time'],
			];

			$items[ $time['country'] ] = $data;
		}

		return $items;
	}
}
