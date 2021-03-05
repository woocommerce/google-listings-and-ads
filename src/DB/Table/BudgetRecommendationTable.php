<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\FirstInstallInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetRecommendationTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Tables
 */
class BudgetRecommendationTable extends Table implements FirstInstallInterface {

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
    currency varchar(3) NOT NULL,
    country varchar(2) NOT NULL,
    daily_budget_low int(11) NOT NULL,
    daily_budget_high int(11) NOT NULL,
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
			'id'                => true,
			'currency'          => true,
			'country'           => true,
			'daily_budget_low'  => true,
			'daily_budget_high' => true,
		];
	}

	/**
	 * Load packaged recommendation data on the first install of GLA.
	 *
	 * Inserts 500 records at a time.
	 */
	public function first_install(): void {
		$path       = $this->get_root_dir() . '/data/budget-recommendations.csv';
		$chunk_size = 500;

		if ( file_exists( $path ) ) {
			$csv     = array_map( 'str_getcsv', file( $path ) );
			$headers = array_shift( $csv );
			if ( empty( $csv ) ) {
				return;
			}

			$values       = [];
			$placeholders = [];

			// Build placeholders for each row, and add values to data array
			foreach ( $csv as $row ) {

				if ( empty( $row ) ) {
					continue;
				}

				$row_placeholders = [];
				foreach ( $row as $value ) {
					$values[]           = $value;
					$row_placeholders[] = is_numeric( $value ) ? '%d' : '%s';
				}
				$placeholders[] = '(' . implode( ', ', $row_placeholders ) . ')';

				if ( count( $placeholders ) >= $chunk_size ) {
					$this->insert_chunk( $placeholders, $values );
					$placeholders = [];
					$values       = [];
				}
			}

			$this->insert_chunk( $placeholders, $values );
		}
	}

	/**
	 * Insert a chunk of budget recommendations
	 *
	 * @param string[] $placeholders
	 * @param array    $values
	 */
	private function insert_chunk( array $placeholders, array $values ): void {
		$sql  = "INSERT INTO `{$this->get_sql_safe_name()}` (currency,country,daily_budget_low,daily_budget_high) VALUES\n";
		$sql .= implode( ",\n", $placeholders );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->wpdb->query( $this->wpdb->prepare( $sql, $values ) );
	}
}
