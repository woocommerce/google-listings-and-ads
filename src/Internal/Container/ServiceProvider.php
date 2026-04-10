<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for all service providers.
 *
 * Replaces League's AbstractServiceProvider so the plugin no longer depends
 * on league/container. The API surface is intentionally narrow: providers
 * receive the container via setContainer() and implement register() to
 * declare their bindings.
 *
 * Unlike League's deferred-provider model, every provider's register() is
 * called eagerly when the container is constructed — there is no $provides
 * array and no lazy booting. See docs in PluginContainer for the rationale.
 */
abstract class ServiceProvider {

	/**
	 * @var PluginContainer|null
	 */
	protected $container;

	public function setContainer( PluginContainer $container ): void {
		$this->container = $container;
	}

	public function getContainer(): PluginContainer {
		if ( null === $this->container ) {
			throw new ContainerException(
				sprintf(
					'%s::getContainer() called before the provider was registered with a container.',
					static::class
				)
			);
		}
		return $this->container;
	}

	/**
	 * Declare the provider's bindings on $this->container. Called exactly
	 * once per container, at container construction time.
	 */
	abstract public function register(): void;
}
