<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Activateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB
 */
class DBController implements Service, Registerable, Activateable {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var DBTable[]
	 */
	protected $tables;

	/**
	 * DBController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Activate the service.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->setup_table_controllers();
		foreach ( $this->tables as $table ) {
			$table->install();
		}
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		// TODO: Implement register() method.
	}

	/**
	 * Set up each of the table controllers.
	 */
	protected function setup_table_controllers() {
		$this->tables = $this->container->get( 'db_table' );
	}
}
