<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\Jetpack\Config;
use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Argument\RawArgument;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;

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
		Config::class              => true,
		Manager::class             => true,
		ISO3166DataProvider::class => true,
	];

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$jetpack_id = new RawArgument( 'connection-test' );
		$this->share( Manager::class )->addArgument( $jetpack_id );

		$this->share( Config::class )->addMethodCall(
			'ensure',
			[
				new RawArgument( 'connection' ),
				[
					'slug' => $jetpack_id->getValue(),
					'name' => __( 'Connection Test', 'google-listings-and-ads' ),
				],
			]
		);

		$this->share_interface( ISO3166DataProvider::class, ISO3166::class );
	}
}
