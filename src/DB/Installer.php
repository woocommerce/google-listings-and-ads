<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\FirstInstallInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\InstallableInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class Installer implements Service, FirstInstallInterface, InstallableInterface {

	use ValidateInterface;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * @var Table[]
	 */
	protected $tables;

	/**
	 * DBController constructor.
	 *
	 * @param Table[]          $tables
	 * @param OptionsInterface $options
	 */
	public function __construct( array $tables, OptionsInterface $options ) {
		$this->tables  = $tables;
		$this->options = $options;
		$this->validate_table_controllers();
	}

	/**
	 * Run installation logic for this class.
	 */
	public function install(): void {
		foreach ( $this->tables as $table ) {
			$table->install();
		}
	}

	/**
	 * Logic to run when the plugin is first installed.
	 */
	public function first_install(): void {
		foreach ( $this->tables as $table ) {
			if ( $table instanceof FirstInstallInterface ) {
				$table->first_install();
			}
		}
	}

	/**
	 * Set up each of the table controllers.
	 */
	protected function validate_table_controllers() {
		foreach ( $this->tables as $table ) {
			$this->validate_instanceof( $table, Table::class );
		}
	}
}
