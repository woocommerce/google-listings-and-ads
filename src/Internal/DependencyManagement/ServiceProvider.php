<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleForWC\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleForWC\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement
 */
class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Array of classes provided by this container.
	 *
	 * @var array
	 */
	protected $provides = [
		AssetsHandlerInterface::class => 1,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_helpers();
	}

	/**
	 * Register helper classes that are not their own services.
	 */
	protected function register_helpers(): void {
		$this->share( AssetsHandlerInterface::class, AssetsHandler::class );
	}

	/**
	 * Returns a boolean if checking whether this provider provides a specific
	 * service or returns an array of provided services if no argument passed.
	 *
	 * @param string $service
	 *
	 * @return boolean
	 */
	public function provides( string $service ): bool {
		return array_key_exists( $service, $this->provides );
	}
}
