<?php
declare( strict_types=1 );

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use ArrayObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductIDMap
 *
 * Represents an array of WooCommerce product IDs mapped to Google product IDs as their key.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class ProductIDMap extends ArrayObject implements ValueInterface {

	/**
	 * ProductIDMap constructor.
	 *
	 * @param int[]  $array         An array of WooCommerce product IDs mapped to Google product IDs as their key.
	 * @param int    $flags         Flags to control the behaviour of the ArrayObject object.
	 * @param string $iteratorClass Specify the class that will be used for iteration of the ArrayObject object. ArrayIterator is the default class used.
	 *
	 * @throws InvalidValue When an invalid WooCommerce product ID or Google product ID is provided in the map.
	 */
	public function __construct( $array = [], $flags = 0, $iteratorClass = 'ArrayIterator' ) {
		$this->validate( $array );

		parent::__construct( $array, $flags, $iteratorClass );
	}

	/**
	 * Get the value of the object.
	 *
	 * @return array
	 */
	public function get(): array {
		return $this->getArrayCopy();
	}

	/**
	 * @param array $array
	 *
	 * @throws InvalidValue When an invalid WooCommerce product ID or Google product ID is provided in the map.
	 */
	protected function validate( array $array ) {
		foreach ( $array as $google_id => $wc_product_id ) {
			$wc_product_id = filter_var( $wc_product_id, FILTER_VALIDATE_INT );
			if ( false === $wc_product_id ) {
				throw InvalidValue::not_integer( 'product_id' );
			}
			if ( ! is_string( $google_id ) ) {
				throw InvalidValue::not_string( 'google_id' );
			}
		}
	}
}
