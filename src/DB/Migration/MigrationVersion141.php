<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class MigrationVersion1_4_1
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.4.1
 */
class MigrationVersion141 extends AbstractMigration {

	/**
	 * @var MerchantIssueTable
	 */
	protected $mc_issues_table;

	/**
	 * MigrationVersion141 constructor.
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
		return '1.4.1';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		if ( $this->mc_issues_table->exists() && $this->mc_issues_table->has_index( 'product_issue' ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->mc_issues_table->get_name() )}` DROP INDEX `product_issue`" );
		}
	}
}
