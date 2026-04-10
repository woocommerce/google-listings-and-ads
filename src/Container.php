<?php
/**
 * Container class file.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container\PluginContainer;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\AdminServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\CoreServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\DBServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\GoogleServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\IntegrationServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\JobServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\ProxyServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\RESTServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\ThirdPartyServiceProvider;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

/**
 * PSR11 compliant dependency injection container for Google for WooCommerce.
 *
 * Classes in the `src` directory should specify dependencies from that directory via constructor arguments
 * with type hints. If an instance of the container itself is needed, the type hint to use is
 * Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface.
 *
 * Classes in the `src` directory should interact with anything outside (especially WordPress functions) by using
 * the classes in the `Proxies` directory. The exception is idempotent functions (e.g. `wp_parse_url`), which
 * can be used directly.
 *
 * Class registration should be done via service providers that inherit from
 * Internal\DependencyManagement\AbstractServiceProvider and live under
 * src/Internal/DependencyManagement/. All concrete provider classes must be listed in $service_providers.
 *
 * ---
 *
 * This class is a thin PSR-11 adapter on top of PluginContainer. The real
 * container logic (definitions, arguments resolution, inflectors, tagging)
 * lives in Internal\Container\PluginContainer. This class:
 *
 *   1. Constructs a PluginContainer instance.
 *   2. Registers itself on that inner container as ContainerInterface::class,
 *      so any service that depends on ContainerInterface receives this
 *      public wrapper (not the internal implementation).
 *   3. Installs the ContainerAwareInterface inflector so classes implementing
 *      that interface have set_container() called after construction.
 *   4. Eagerly instantiates every configured ServiceProvider, respects the
 *      Conditional::is_needed() gate, and passes each one through
 *      addServiceProvider() — which calls the provider's register() method
 *      immediately. Unlike the previous League-based implementation, there
 *      is no deferred booting.
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
		AdminServiceProvider::class,
	];

	/**
	 * The underlying plugin container.
	 *
	 * @var PluginContainer
	 */
	private $container;

	/**
	 * Container constructor.
	 */
	public function __construct() {
		$this->container = new PluginContainer();

		// Any service that depends on ContainerInterface receives this
		// wrapper, not the inner container. Callers use only PSR-11 methods
		// so the wrapper is a complete substitute for the inner container
		// from the outside.
		$this->container->addShared( ContainerInterface::class, $this );

		// Classes that implement ContainerAwareInterface receive the
		// container via set_container() immediately after construction.
		$this->container->inflector( ContainerAwareInterface::class )
			->invokeMethod( 'set_container', [ ContainerInterface::class ] );

		foreach ( $this->service_providers as $service_provider_class ) {
			$service_provider = new $service_provider_class();
			$implements       = class_implements( $service_provider );
			if ( array_key_exists( Conditional::class, $implements ) && ! $service_provider->is_needed() ) {
				continue;
			}

			// Eager registration: this immediately calls the provider's
			// register() method and wires up all its bindings.
			$this->container->addServiceProvider( $service_provider );
		}
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\NotFoundExceptionInterface  No entry was found for **this** identifier.
	 * @throws \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerExceptionInterface Error while retrieving the entry.
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
	 * It does however mean that `get($id)` will not throw a NotFoundExceptionInterface.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		return $this->container->has( $id );
	}
}
