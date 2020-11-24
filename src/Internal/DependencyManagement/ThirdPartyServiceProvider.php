<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class ThirdPartyServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
class ThirdPartyServiceProvider extends AbstractServiceProvider {

	use PluginHelper;

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [
		Manager::class => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( Manager::class )->addArgument( "{$this->get_slug()}-connection" );
	}
}
