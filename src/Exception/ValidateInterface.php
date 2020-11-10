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
	 * @param string $class     The class name.
	 * @param string $interface The interface name.
	 *
	 * @throws InvalidClass When the given class does not implement the interface.
	 */
	protected function validate_interface( string $class, string $interface ) {
		$implements = class_implements( $class );
		if ( ! array_key_exists( $interface, $implements ) ) {
			throw InvalidClass::should_implement( $class, $interface );
		}
	}
}
