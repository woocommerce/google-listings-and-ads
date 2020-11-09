<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;

/**
 * Class ServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
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
}
