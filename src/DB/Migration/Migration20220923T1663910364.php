<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20220923T1663910364 (22nd September 2022)
 *
 * Migration for changing the data type from actions column in MC Issues table
 *
 * We migrate actions column from VARCHAR(100) to TEXT
 *
 * @see https://github.com/woocommerce/google-listings-and-ads/issues/1694
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 * @since x.x.x
 */
class Migration20220923T1663910364 extends AbstractMigration {

	/**
	 * @var MerchantIssueTable
	 */
	protected $mc_issues_table;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb               $wpdb The wpdb object.
	 * @param MerchantIssueTable $mc_issues_table
	 */
	public function __construct( wpdb $wpdb, MerchantIssueTable $mc_issues_table ) {
		parent::__construct( $wpdb );
		$this->mc_issues_table = $mc_issues_table;
	}


	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number. For example: 1.4.1
	 */
	public function get_applicable_version(): string {
		return 'x.x.x';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( $this->mc_issues_table->exists() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->mc_issues_table->get_name() )}` CHANGE `action` `action` TEXT NOT NULL" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
