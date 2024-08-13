<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingTimeTable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20240813T1653383133
 *
 * Migration class to enable min and max time shippings.
 *
 * @see pcTzPl-2qP
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since x.x.x
 */
class Migration20240813T1653383133 extends AbstractMigration {

	/**
	 * @var ShippingTimeTable
	 */
	protected $shipping_time_table;

	/**
	 * Migration constructor.
	 *
	 * @param \wpdb             $wpdb
	 * @param ShippingTimeTable $shipping_time_table
	 */
	public function __construct( \wpdb $wpdb, ShippingTimeTable $shipping_time_table ) {
		parent::__construct( $wpdb );
		$this->shipping_time_table = $shipping_time_table;
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
		if ( $this->shipping_time_table->exists() && ! $this->shipping_time_table->has_column( 'max_time' ) ) {
			$this->wpdb->query( "ALTER TABLE `{$this->wpdb->_escape( $this->shipping_time_table->get_name() )}` Add COLUMN `max_time` bigint(20) NOT NULL default 0" ); // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Fill the new column with the current values
		$this->wpdb->query( "UPDATE `{$this->wpdb->_escape( $this->shipping_time_table->get_name() )}` SET `max_time`=`time` WHERE 1=1" );
	}
}
