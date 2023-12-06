<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20231109T1653383133
 *
 * Migration class to reload the default Ads budgets recommendations provided by Google on 9 Nov 2023
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 2.5.13
 */
class Migration20231109T1653383133 extends AbstractMigration {

	/**
	 * @var BudgetRecommendationTable
	 */
	protected $budget_rate_table;

	/**
	 * Migration constructor.
	 *
	 * @param \wpdb                     $wpdb
	 * @param BudgetRecommendationTable $budget_rate_table
	 */
	public function __construct( \wpdb $wpdb, BudgetRecommendationTable $budget_rate_table ) {
		parent::__construct( $wpdb );
		$this->budget_rate_table = $budget_rate_table;
	}


	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number. For example: 1.4.1
	 */
	public function get_applicable_version(): string {
		return '2.5.13';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( $this->budget_rate_table->exists() && $this->budget_rate_table->has_column( 'daily_budget_low' ) ) {
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->budget_rate_table->get_name() )}` DROP COLUMN `daily_budget_low`" ); // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( $this->budget_rate_table->exists() && $this->budget_rate_table->has_column( 'daily_budget_high' ) ) {
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->budget_rate_table->get_name() )}` DROP COLUMN `daily_budget_high`" ); // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		}

		$this->budget_rate_table->reload_data();
	}
}
