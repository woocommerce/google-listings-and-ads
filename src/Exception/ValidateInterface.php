<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

/**
 * Trait ValidateInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
trait ValidateInterface {

	/**
	 * Validate that a class implements a given interface.
	 *
	 * @param string $class_name      The class name.
	 * @param string $interface_name The interface name.
	 *
	 * @throws InvalidClass When the given class does not implement the interface.
	 */
	protected function validate_interface( string $class_name, string $interface_name ) {
		$implements = class_implements( $class_name );
		if ( ! array_key_exists( $interface_name, $implements ) ) {
			throw InvalidClass::should_implement( $class_name, $interface_name );
		}
	}

	/**
	 * Validate that an object is an instance of an interface.
	 *
	 * @param object $object_instance The object to validate.
	 * @param string $interface_name  The interface name.
	 *
	 * @throws InvalidClass When the given object does not implement the interface.
	 */
	protected function validate_instanceof( $object_instance, string $interface_name ) {
		$class_name = '';
		if ( is_object( $object_instance ) ) {
			$class_name = get_class( $object_instance );
		}

		if ( ! $object_instance instanceof $interface_name ) {
			throw InvalidClass::should_implement( $class_name, $interface_name );
		}
	}

	/**
	 * Validate that an object is NOT an instance of an interface.
	 *
	 * @param object $object_instance The object to validate.
	 * @param string $interface_name  The interface name.
	 *
	 * @throws InvalidClass When the given object implements the interface.
	 */
	protected function validate_not_instanceof( $object_instance, string $interface_name ) {
		$class_name = '';
		if ( is_object( $object_instance ) ) {
			$class_name = get_class( $object_instance );
		}

		if ( $object_instance instanceof $interface_name ) {
			throw InvalidClass::should_not_implement( $class_name, $interface_name );
		}
	}
}
