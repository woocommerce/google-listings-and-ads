<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Interface DBTableInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
interface DBTableInterface {

	/**
	 * Install the Database table.
	 */
	public function install(): void;

	/**
	 * Delete the Database table.
	 */
	public function delete(): void;

	/**
	 * Get the name of the Database table.
	 *
	 * @return string
	 */
	public function get_name(): string;
}
