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
	 * Determine whether the table actually exists in the DB.
	 *
	 * @return bool
	 */
	public function exists(): bool;

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

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array;
}
