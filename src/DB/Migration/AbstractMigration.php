<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractMigration
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.4.1
 */
abstract class AbstractMigration implements MigrationInterface {
	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * AbstractMigration constructor.
	 *
	 * @param wpdb $wpdb The wpdb object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}
}
