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
	 * Install the Database table.
	 *
	 * Add data if there is none.
	 */
	public function install(): void {
		parent::install();

		// Load the data if the table is empty.
		// phpcs:ignore WordPress.DB.PreparedSQL
		$result = $this->wpdb->get_row( "SELECT COUNT(*) AS count FROM `{$this->get_sql_safe_name()}`" );
		if ( empty( $result->count ) ) {
			$this->load_initial_data();
		}
	}

	/**
	 * Get the un-prefixed (raw) table name.
	 *
	 * @return string
	 */
	public static function get_raw_name(): string {
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
	private function load_initial_data(): void {
		$path       = $this->get_root_dir() . '/data/budget-recommendations.csv';
		$chunk_size = 500;

		if ( file_exists( $path ) ) {
			$csv = array_map( 'str_getcsv', file( $path ) );

			// Remove the headers
			array_shift( $csv );

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
