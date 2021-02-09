<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ValidateInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Activateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\Installable;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class DBInstaller implements Service, Installable, Activateable {

	use ValidateInterface;

	/**
	 * The current database version.
	 */
	protected const DB_VERSION = 1;

	/**
	 * @var OptionsInterface
	 */
	protected $options;

	/**
	 * @var DBTable[]
	 */
	protected $tables;

	/**
	 * DBController constructor.
	 *
	 * @param DBTable[]        $tables
	 * @param OptionsInterface $options
	 */
	public function __construct( array $tables, OptionsInterface $options ) {
		$this->tables  = $tables;
		$this->options = $options;
		$this->validate_table_controllers();
	}

	/**
	 * Activate the service.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->install();
	}

	/**
	 * Run installation logic for this class.
	 */
	public function install(): void {
		foreach ( $this->tables as $table ) {
			if ( ! $table->exists() ) {
				$table->install();
			}
		}
	}

	/**
	 * Set up each of the table controllers.
	 */
	protected function validate_table_controllers() {
		foreach ( $this->tables as $table ) {
			$this->validate_instanceof( $table, DBTable::class );
		}
	}
}
