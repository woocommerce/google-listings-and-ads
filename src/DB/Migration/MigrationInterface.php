<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Interface MigrationInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.4.1
 */
interface MigrationInterface {
	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number. For example: 1.4.1
	 */
	public function get_applicable_version(): string;

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void;
}
