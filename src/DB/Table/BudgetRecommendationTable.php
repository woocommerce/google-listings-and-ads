<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetRecommendationTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class BudgetRecommendationTable extends Table {

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
    currency varchar(3) NOT NULL,
    daily_budget_low int(20) NOT NULL,
    daily_budget_high int(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY country_currency (country, currency)
) {$this->get_collation()};
SQL;
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	protected function get_raw_name(): string {
		return 'budget_recommendations';
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
			'currency' => true,
			'rate'     => true,
		];
	}
}
