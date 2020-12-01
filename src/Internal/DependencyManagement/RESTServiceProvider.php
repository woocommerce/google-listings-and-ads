<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter\SettingsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding\GoogleConnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding\JetpackConnectController;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\DefinitionInterface;

/**
 * Class RESTServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class RESTServiceProvider extends AbstractServiceProvider {

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		GoogleConnectController::class  => true,
		JetpackConnectController::class => true,
		SettingsController::class       => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( GoogleConnectController::class );
		$this->share( JetpackConnectController::class, Manager::class );
		$this->share( SettingsController::class, OptionsInterface::class );
	}

	/**
	 * Share a class.
	 *
	 * Overridden to include the RESTServer proxy with all classes.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share( string $class, ...$arguments ): DefinitionInterface {
		return parent::share( $class, RESTServer::class, ...$arguments );
	}
}
