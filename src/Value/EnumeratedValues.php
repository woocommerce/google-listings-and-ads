<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidType;

defined( 'ABSPATH' ) || exit;

/**
 * Class EnumeratedValues
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
abstract class EnumeratedValues {

	/** @var string */
	protected $value;

	/**
	 * Array of possible valid values.
	 *
	 * Data will be validated to ensure it is one of these values.
	 *
	 * @var array
	 */
	protected $valid_values = [];

	/**
	 * EnumeratedValues constructor.
	 *
	 * @param string $value
	 */
	public function __construct( string $value ) {
		$this->validate_value( $value );
		$this->value = $value;
	}

	/**
	 * Validate the that value we received is one of the valid types.
	 *
	 * @param string $value
	 *
	 * @throws InvalidType When the value is not valid.
	 */
	protected function validate_value( string $value ) {
		if ( ! array_key_exists( $value, $this->valid_values ) ) {
			throw InvalidType::from_type( $value, array_keys( $this->valid_values ) );
		}
	}
}
