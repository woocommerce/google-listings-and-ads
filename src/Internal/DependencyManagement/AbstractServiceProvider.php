<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container\Definition;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container\ServiceProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Base service provider for this plugin.
 *
 * Extends the plugin's own ServiceProvider base (src/Internal/Container/)
 * rather than league/container's AbstractServiceProvider — the plugin no
 * longer depends on league/container. See PluginContainer for the rationale.
 *
 * The share/share_with_tags/share_concrete/add helpers keep the same
 * signatures as before so concrete provider classes do not need to change.
 * The only visible difference is the return type: a plugin-local Definition
 * instead of League's DefinitionInterface. Methods called on that return
 * value (addArguments, addArgument, addTag, addMethodCall) are identical.
 */
abstract class AbstractServiceProvider extends ServiceProvider {

	/**
	 * Array of classes this provider declares it provides.
	 *
	 * Historically this drove League's deferred-provider boot machinery.
	 * With the plugin's own container this field is unused at runtime — all
	 * providers are registered eagerly — but it is kept as documentation of
	 * intent and in case external tooling inspects it.
	 *
	 * @var array
	 */
	protected $provides = [];

	/**
	 * Historical provides() check. Not called by the plugin's container,
	 * kept for compatibility with any external caller that inspects it.
	 */
	public function provides( string $service ): bool {
		return array_key_exists( $service, $this->provides );
	}

	/**
	 * Share a class with a concrete implementation. Used for interface
	 * bindings and for wrapping factory closures.
	 *
	 * @param string $interface_name The identifier (typically an interface).
	 * @param mixed  $concrete       A class name, closure, object, or Definition.
	 */
	protected function share_concrete( string $interface_name, $concrete = null ): Definition {
		return $this->getContainer()->addShared( $interface_name, $concrete );
	}

	/**
	 * Share a class and auto-tag it with every interface it implements.
	 * `get($interface_name)` will then return the collection of all services
	 * tagged with that interface.
	 *
	 * @param string $class_name   The class to share.
	 * @param mixed  ...$arguments Constructor arguments.
	 */
	protected function share_with_tags( string $class_name, ...$arguments ): Definition {
		$definition = $this->share( $class_name, ...$arguments );
		foreach ( class_implements( $class_name ) as $interface_name ) {
			$definition->addTag( $interface_name );
		}
		return $definition;
	}

	/**
	 * Share a class via a factory closure, auto-tagging it with every
	 * interface it implements (same tagging semantics as share_with_tags).
	 *
	 * Unlike share_with_tags, the factory is called lazily — the first time
	 * the service is resolved, not when the provider's register() runs. This
	 * is the right choice when the concrete construction depends on state
	 * that isn't available at registration time (for example, classes from
	 * another plugin that loads after ours in plugins_loaded order).
	 *
	 * @param string   $class_name The class the factory returns.
	 * @param callable $factory    Factory closure. Receives the container as its only argument.
	 */
	protected function share_factory_with_tags( string $class_name, callable $factory ): Definition {
		$definition = $this->share_concrete( $class_name, $factory );
		foreach ( class_implements( $class_name ) as $interface_name ) {
			$definition->addTag( $interface_name );
		}
		return $definition;
	}

	/**
	 * Share a class as a singleton. Every get() returns the same instance.
	 *
	 * @param string $class_name   The class to share.
	 * @param mixed  ...$arguments Constructor arguments.
	 */
	protected function share( string $class_name, ...$arguments ): Definition {
		return $this->getContainer()->addShared( $class_name )->addArguments( $arguments );
	}

	/**
	 * Add a class as a transient binding. Every get() returns a new instance.
	 *
	 * @param string $class_name   The class to add.
	 * @param mixed  ...$arguments Constructor arguments.
	 */
	protected function add( string $class_name, ...$arguments ): Definition {
		return $this->getContainer()->add( $class_name )->addArguments( $arguments );
	}

	/**
	 * Share a class with tags only if its Conditional::is_needed() returns
	 * true. Classes that do not implement Conditional are shared unconditionally.
	 *
	 * @param string $class_name   The class to share.
	 * @param mixed  ...$arguments Constructor arguments.
	 */
	protected function conditionally_share_with_tags( string $class_name, ...$arguments ) {
		$implements = class_implements( $class_name );
		if ( array_key_exists( Conditional::class, $implements ) ) {
			/** @var Conditional $class_name */
			if ( ! $class_name::is_needed() ) {
				return;
			}
		}

		$this->provides[ $class_name ] = true;
		$this->share_with_tags( $class_name, ...$arguments );
	}
}
