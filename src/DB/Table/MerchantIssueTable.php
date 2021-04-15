<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantIssueTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class MerchantIssueTable extends Table {

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
    `id` bigint(20) NOT NULL  AUTO_INCREMENT,
    `product_id` bigint(20) NOT NULL,
    `issue` varchar(200) NOT NULL,
    `code` varchar(100) NOT NULL,
    `product` varchar(100) NOT NULL,
    `action` varchar(100) NOT NULL,
    `action_url` varchar(100) NOT NULL,
    `applicable_countries` text NOT NULL,
    `created_at` datetime on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY `id` (`id`),
    UNIQUE `product_issue` (`product_id`, `issue`)
) {$this->get_collation()};
SQL;
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	protected function get_raw_name(): string {
		return 'merchant_issues';
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'id'                   => true,
			'product_id'           => true,
			'code'                 => true,
			'issue'                => true,
			'product'              => true,
			'action'              => true,
			'action_url'              => true,
			'applicable_countries' => true,
		];
	}
}
