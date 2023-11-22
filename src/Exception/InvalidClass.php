<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use LogicException;

/**
 * Class InvalidClass
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidClass extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new instance of the exception when a class should implement an interface but does not.
	 *
	 * @param string $class_name     The class name.
	 * @param string $interface_name The interface name.
	 *
	 * @return static
	 */
	public static function should_implement( string $class_name, string $interface_name ) {
		return new static(
			sprintf(
				'The class "%s" must implement the "%s" interface.',
				$class_name,
				$interface_name
			)
		);
	}

	/**
	 * Create a new instance of the exception when a class should NOT implement an interface but it does.
	 *
	 * @param string $class_name     The class name.
	 * @param string $interface_name The interface name.
	 *
	 * @return static
	 */
	public static function should_not_implement( string $class_name, string $interface_name ): InvalidClass {
		return new static(
			sprintf(
				'The class "%s" must NOT implement the "%s" interface.',
				$class_name,
				$interface_name
			)
		);
	}

	/**
	 * Create a new instance of the exception when a class should override a method but does not.
	 *
	 * @param string $class_name  The class name.
	 * @param string $method_name The method name.
	 *
	 * @return static
	 */
	public static function should_override( string $class_name, string $method_name ) {
		return new static(
			sprintf(
				'The class "%s" must override the "%s()" method.',
				$class_name,
				$method_name
			)
		);
	}
}
