<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

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
	 * Installer constructor.
	 *
	 * @param TableManager $table_manager
	 */
	public function __construct( TableManager $table_manager ) {
		$this->table_manager = $table_manager;
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
