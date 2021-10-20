<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneNumber
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 *
 * @since 1.5.0
 */
class PhoneNumber implements CastableValueInterface, ValueInterface {

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * PhoneNumber constructor.
	 *
	 * @param string $value The value.
	 *
	 * @throws InvalidValue When an invalid phone number is provided.
	 */
	public function __construct( string $value ) {
		if ( ! self::validate_phone_number( $value ) ) {
			throw new InvalidValue( 'Invalid phone number!' );
		}

		$this->value = self::sanitize_phone_number( $value );
	}

	/**
	 * Get the value of the object.
	 *
	 * @return string
	 */
	public function get(): string {
		return $this->value;
	}

	/**
	 * Cast a value and return a new instance of the class.
	 *
	 * @param mixed $value Mixed value to cast to class type.
	 *
	 * @return PhoneNumber
	 */
	public static function cast( $value ): PhoneNumber {
		return new self( self::sanitize_phone_number( $value ) );
	}

	/**
	 * Validate that the phone number doesn't contain invalid characters.
	 * Allowed: ()-.0123456789 and space
	 *
	 * @param string|int $phone_number The phone number to validate.
	 *
	 * @return bool
	 */
	public static function validate_phone_number( $phone_number ): bool {
		// Disallowed characters.
		if ( is_string( $phone_number ) && preg_match( '/[^0-9() \-.+]/', $phone_number ) ) {
			return false;
		}

		// Don't allow integer 0
		return ! empty( $phone_number );
	}

	/**
	 * Sanitize the phone number, leaving only `+` (plus) and numbers.
	 *
	 * @param string|int $phone_number The phone number to sanitize.
	 *
	 * @return string
	 */
	public static function sanitize_phone_number( $phone_number ): string {
		return preg_replace( '/[^+0-9]/', '', "$phone_number" );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get();
	}
}
