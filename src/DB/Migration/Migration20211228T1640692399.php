<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20211228T1640692399
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since x.x.x
 */
class Migration20211228T1640692399 extends AbstractMigration {

	/**
	 * @var ShippingRateTable
	 */
	protected $shipping_rate_table;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb              $wpdb The wpdb object.
	 * @param ShippingRateTable $shipping_rate_table
	 */
	public function __construct( wpdb $wpdb, ShippingRateTable $shipping_rate_table ) {
		parent::__construct( $wpdb );
		$this->shipping_rate_table = $shipping_rate_table;
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
		if ( $this->shipping_rate_table->exists() ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->shipping_rate_table->get_name() )}` ADD COLUMN `method` varchar(30) NOT NULL DEFAULT 'flat_rate' AFTER `country`" );
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->shipping_rate_table->get_name() )}` ALTER COLUMN `method` DROP DEFAULT" );
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->shipping_rate_table->get_name() )}` ADD COLUMN `options` text DEFAULT NULL" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
