<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migrator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 1.4.1
 */
class Migrator implements Service {

	/**
	 * @var MigrationInterface[]
	 */
	protected $migrations;

	/**
	 * Migrator constructor.
	 *
	 * @param MigrationInterface[] $migrations An array of all available migrations.
	 */
	public function __construct( array $migrations ) {
		$this->migrations = $migrations;
	}

	/**
	 * Run migrations.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function migrate( string $old_version, string $new_version ): void {
		// bail if both versions are equal.
		if ( 0 === version_compare( $old_version, $new_version ) ) {
			return;
		}

		foreach ( $this->migrations as $migration ) {
			if ( $this->can_apply( $migration->get_applicable_version(), $old_version, $new_version ) ) {
				$migration->apply();
			}
		}
	}

	/**
	 * @param string $migration_version The applicable version of the migration.
	 * @param string $old_version       Previous version before updating.
	 * @param string $new_version       Current version after updating.
	 *
	 * @return bool True if migration should be applied.
	 */
	protected function can_apply( string $migration_version, string $old_version, string $new_version ): bool {
		return version_compare( $old_version, $new_version, '<' ) &&
			   version_compare( $old_version, $migration_version, '<' ) &&
			   version_compare( $migration_version, $new_version, '<=' );
	}
}
