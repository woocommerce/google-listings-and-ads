<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration\Migrator;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\FirstInstallInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class Installer implements Service, FirstInstallInterface, InstallableInterface {

	/**
	 * @var TableManager
	 */
	protected $table_manager;

	/**
	 * @var Migrator
	 */
	protected $migrator;

	/**
	 * Installer constructor.
	 *
	 * @param TableManager $table_manager
	 * @param Migrator     $migrator
	 */
	public function __construct( TableManager $table_manager, Migrator $migrator ) {
		$this->table_manager = $table_manager;
		$this->migrator      = $migrator;
	}

	/**
	 * Run installation logic for this class.
	 *
	 * @param string $old_version Previous version before updating.
	 * @param string $new_version Current version after updating.
	 */
	public function install( string $old_version, string $new_version ): void {
		foreach ( $this->table_manager->get_tables() as $table ) {
			$table->install();
		}

		// Run migrations.
		$this->migrator->migrate( $old_version, $new_version );
	}

	/**
	 * Logic to run when the plugin is first installed.
	 */
	public function first_install(): void {
		foreach ( $this->table_manager->get_tables() as $table ) {
			if ( $table instanceof FirstInstallInterface ) {
				$table->first_install();
			}
		}
	}
}
