<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingTimeTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Table
 */
class ShippingTimeTable extends Table {

	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	protected function get_install_query(): string {
		return <<< SQL
CREATE TABLE `{$this->get_sql_safe_name()}` (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    country varchar(2) NOT NULL,
    time bigint(20) NOT NULL default 0,
	max_time bigint(20) NOT NULL default 0,
    PRIMARY KEY (id),
    KEY country (country)
) {$this->get_collation()};
SQL;
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
		return 'shipping_times';
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'id'       => true,
			'country'  => true,
			'time'     => true,
			'max_time' => true,
		];
	}
}
