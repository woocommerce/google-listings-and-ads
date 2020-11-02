<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer as WCExtendedContainer;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Vendor\League\Container\Definition\Definition;
use Automattic\WooCommerce\Vendor\League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface;

/**
 * ExtendedContainer class.
 *
 * @package Automattic\WooCommerce\GoogleForWC\Internal\DependencyManagement
 */
class ExtendedContainer extends WCExtendedContainer {

	/**
	 * The root namespace of all of our classes in the src/ directory.
	 *
	 * @var string
	 */
	protected $namespace = 'Automattic\\WooCommerce\\GoogleForWC\\';

	/**
	 * Array of classes that are allowed despite not being part of the namespace.
	 *
	 * The keys of the array are what matter in determining allowed classes.
	 *
	 * @var array
	 */
	protected $registration_whitelist = [
		ContainerInterface::class => true,
	];

	/**
	 * Register a class in the container.
	 *
	 * @param string    $class_name Class name.
	 * @param mixed     $concrete How to resolve the class with `get`: a factory callback, a concrete instance, another class name, or null to just create an instance of the class.
	 * @param bool|null $shared Whether the resolution should be performed only once and cached.
	 *
	 * @return DefinitionInterface The generated definition for the container.
	 * @throws ContainerException Invalid parameters.
	 */
	public function add( string $class_name, $concrete = null, bool $shared = null ) : DefinitionInterface {
		if ( ! $this->is_class_allowed( $class_name ) ) {
			throw new ContainerException( "You cannot add '{$class_name}', only classes in the {$this->namespace} namespace are allowed." );
		}

		$concrete_class = $this->get_class_from_concrete( $concrete );
		if ( isset( $concrete_class ) && ! $this->is_class_allowed( $concrete_class ) ) {
			throw new ContainerException( "You cannot add concrete '{$concrete_class}', only classes in the {$this->namespace} namespace are allowed." );
		}

		// We want to use a definition class that does not support constructor injection to avoid accidental usage.
		if ( ! $concrete instanceof DefinitionInterface ) {
			$concrete = new Definition( $class_name, $concrete );
		}

		return parent::add( $class_name, $concrete, $shared );
	}

	/**
	 * Replace an existing registration with a different concrete.
	 *
	 * @param string $class_name The class name whose definition will be replaced.
	 * @param mixed  $concrete The new concrete (same as "add").
	 *
	 * @return DefinitionInterface The modified definition.
	 * @throws ContainerException Invalid parameters.
	 */
	public function replace( string $class_name, $concrete ) : DefinitionInterface {
		if ( ! $this->has( $class_name ) ) {
			throw new ContainerException( "The container doesn't have '{$class_name}' registered, please use 'add' instead of 'replace'." );
		}

		$concrete_class = $this->get_class_from_concrete( $concrete );
		if ( isset( $concrete_class ) && ! $this->is_class_allowed( $concrete_class ) ) {
			throw new ContainerException( "You cannot use concrete '{$concrete_class}', only classes in the {$this->namespace} namespace are allowed." );
		}

		return $this->extend( $class_name )->setConcrete( $concrete );
	}

	/**
	 * Checks to see whether or not a class is allowed to be registered.
	 *
	 * @param string $class_name The class to check.
	 *
	 * @return bool True if the class is allowed to be registered, false otherwise.
	 */
	protected function is_class_allowed( string $class_name ): bool {
		return StringUtil::starts_with( $class_name, $this->namespace ) || array_key_exists( $class_name, $this->registration_whitelist );
	}
}
