<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Definition class for the Attribute Mapping Rules Table
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class AttributeMappingRulesTable extends Table {

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
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `attribute` varchar(255) NOT NULL,
    `source` varchar(100) NOT NULL,
    `category_condition_type` varchar(10) NOT NULL,
    `categories` text DEFAULT '',
    PRIMARY KEY `id` (`id`)
) {$this->get_collation()};
SQL;
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
		return 'attribute_mapping_rules';
	}


	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'id'                      => true,
			'attribute'               => true,
			'source'                  => true,
			'category_condition_type' => true,
			'categories'              => true,
		];
	}
}
