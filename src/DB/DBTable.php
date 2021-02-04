<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBTable
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
abstract class DBTable implements DBTableInterface {

	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * DBTable constructor.
	 *
	 * @param WP $wp The WP proxy object.
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Install the Database table.
	 */
	public function install(): void {
		// TODO: Implement install() method.
	}

	/**
	 * Delete the Database table.
	 */
	public function delete(): void {
		// TODO: Implement delete() method.
	}

	/**
	 * Get the schema for the DB.
	 *
	 * This should be a SQL string for creating the DB table.
	 *
	 * @return string
	 */
	abstract protected function get_db_schema(): string;
}
