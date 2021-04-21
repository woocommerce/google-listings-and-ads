<?php
/**
 * Container class file.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\CoreServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\DBServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\IntegrationServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\JobServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\ProxyServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\RESTServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\ThirdPartyServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container as LeagueContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * PSR11 compliant dependency injection container for Google Listings and Ads.
 *
 * Classes in the `src` directory should specify dependencies from that directory via constructor arguments
 * with type hints. If an instance of the container itself is needed, the type hint to use is
 * \Psr\Container\ContainerInterface.
 *
 * Classes in the `src` directory should interact with anything outside (especially WordPress functions) by using
 * the classes in the `Proxies` directory. The exception is idempotent functions (e.g. `wp_parse_url`), which
 * can be used directly.
 *
 * Class registration should be done via service providers that inherit from
 * \Automattic\WooCommerce\Internal\DependencyManagement and those should go in the
 * `src/Internal/DependencyManagement/ServiceProviders` folder unless there's a good reason to put them elsewhere.
 * All the service provider class names must be in the `$service_providers` array.
 */
final class Container implements ContainerInterface {

	/**
	 * The list of service provider classes to register.
	 *
	 * @var string[]
	 */
	private $service_providers = [
		ProxyServiceProvider::class,
		CoreServiceProvider::class,
		RESTServiceProvider::class,
		ThirdPartyServiceProvider::class,
		GoogleServiceProvider::class,
		JobServiceProvider::class,
		IntegrationServiceProvider::class,
		DBServiceProvider::class,
	];

	/**
	 * The underlying container.
	 *
	 * @var LeagueContainer
	 */
	private $container;

	/**
	 * Class constructor.
	 *
	 * @param LeagueContainer|null $container
	 */
	public function __construct( ?LeagueContainer $container = null ) {
		$this->container = $container ?? new LeagueContainer();
		$this->container->share( ContainerInterface::class, $this );
		$this->container->inflector( ContainerAwareInterface::class )
			->invokeMethod( 'set_container', [ ContainerInterface::class ] );

		foreach ( $this->service_providers as $service_provider_class ) {
			$this->container->addServiceProvider( $service_provider_class );
		}
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return mixed Entry.
	 */
	public function get( $id ) {
		return $this->container->get( $id );
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		return $this->container->has( $id );
	}
}
