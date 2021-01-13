<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidArray;

defined( 'ABSPATH' ) || exit;

/**
 * Class ArrayWithRequiredKeys
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
abstract class ArrayWithRequiredKeys {

	/**
	 * The provided data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Array of required keys. Should be in key => value format.
	 *
	 * @var array
	 */
	protected $required_keys = [];

	/**
	 * ArrayWithRequiredKeys constructor.
	 *
	 * @param array $data
	 */
	public function __construct( array $data ) {
		$this->validate_keys( $data );
		$this->data = $data;
	}

	/**
	 * Validate that we have all of the keys that we require.
	 *
	 * @param array $data Array of provided data.
	 *
	 * @throws InvalidArray When any of the required keys are missing.
	 */
	protected function validate_keys( array $data ) {
		$missing = array_diff_key( $this->required_keys, $data );
		if ( ! empty( $missing ) ) {
			throw InvalidArray::missing_keys( static::class, array_keys( $missing ) );
		}
	}
}
