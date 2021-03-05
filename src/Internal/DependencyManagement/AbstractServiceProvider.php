<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Definition\DefinitionInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\ServiceProvider\AbstractServiceProvider as LeagueProvider;

/**
 * Class AbstractServiceProvider
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement
 */
abstract class AbstractServiceProvider extends LeagueProvider {

	/**
	 * Array of classes provided by this container.
	 *
	 * Keys should be the class name, and the value can be anything (like `true`).
	 *
	 * @var array
	 */
	protected $provides = [];

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

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->provides as $class => $provided ) {
			$this->share( $class );
		}
	}

	/**
	 * Add an interface to the container.
	 *
	 * @param string      $interface The interface to add.
	 * @param string|null $concrete  (Optional) The concrete class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_concrete( string $interface, $concrete = null ): DefinitionInterface {
		return $this->getLeagueContainer()->share( $interface, $concrete );
	}

	/**
	 * Share a class and add interfaces as tags.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_with_tags( string $class, ...$arguments ): DefinitionInterface {
		$definition = $this->share( $class, ...$arguments );
		foreach ( class_implements( $class ) as $interface ) {
			$definition->addTag( $interface );
		}

		return $definition;
	}

	/**
	 * Share a class.
	 *
	 * Shared classes will always return the same instance of the class when the class is requested
	 * from the container.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function share( string $class, ...$arguments ): DefinitionInterface {
		return $this->getLeagueContainer()->share( $class )->addArguments( $arguments );
	}

	/**
	 * Add a class.
	 *
	 * Classes will return a new instance of the class when the class is requested from the container.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 *
	 * @return DefinitionInterface
	 */
	protected function add( string $class, ...$arguments ): DefinitionInterface {
		return $this->getLeagueContainer()->add( $class )->addArguments( $arguments );
	}

	/**
	 * Maybe share a class and add interfaces as tags.
	 *
	 * This will also check any classes that implement the Conditional interface and only add them if
	 * they are needed.
	 *
	 * @param string $class        The class name to add.
	 * @param mixed  ...$arguments Constructor arguments for the class.
	 */
	protected function conditionally_share_with_tags( string $class, ...$arguments ) {
		$implements = class_implements( $class );
		if ( array_key_exists( Conditional::class, $implements ) ) {
			/** @var Conditional $class */
			if ( ! $class::is_needed() ) {
				return;
			}
		}

		$this->provides[ $class ] = true;
		$this->share_with_tags( $class, ...$arguments );
	}
}
