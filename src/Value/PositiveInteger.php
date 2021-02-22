<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class PositiveInteger
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class PositiveInteger implements CastableValueInterface, ValueInterface {

	/**
	 * @var int
	 */
	protected $value;

	/**
	 * PositiveInteger constructor.
	 *
	 * @param int $value The value.
	 *
	 * @throws InvalidValue When a negative integer is provided.
	 */
	public function __construct( int $value ) {
		$abs_value = absint( $value );
		if ( $abs_value !== $value ) {
			throw InvalidValue::negative_integer( __METHOD__ );
		}

		$this->value = $value;
	}

	/**
	 * Get the value of the object.
	 *
	 * @return int
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * Cast a value and return a new instance of the class.
	 *
	 * @param mixed $value Mixed value to cast to class type.
	 *
	 * @return PositiveInteger
	 */
	public static function cast( $value ): PositiveInteger {
		return new self( absint( $value ) );
	}
}
