<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\BudgetRecommendationTable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20220524T1653383133
 *
 * Migration class to reload the default Ads budgets recommendations
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.13.3
 */
class Migration20220524T1653383133 extends AbstractMigration {

	/**
	 * @var BudgetRecommendationTable
	 */
	protected $budget_rate_table;

	/**
	 * Migration constructor.
	 *
	 * @param BudgetRecommendationTable $budget_rate_table
	 */
	public function __construct( BudgetRecommendationTable $budget_rate_table ) {
		$this->budget_rate_table = $budget_rate_table;
	}


	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number. For example: 1.4.1
	 */
	public function get_applicable_version(): string {
		return '1.13.3';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		$this->budget_rate_table->reload_data();
	}
}
