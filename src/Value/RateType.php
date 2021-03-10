<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class RateType
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class RateType extends EnumeratedValues implements ValueInterface {

	/**
	 * Array of possible valid values.
	 *
	 * Data will be validated to ensure it is one of these values.
	 *
	 * @var array
	 */
	protected $valid_values = [
		'flat'   => true,
		'manual' => true,
	];

	/**
	 * Get the value of the object.
	 *
	 * @return mixed
	 */
	public function get(): string {
		return $this->value;
	}
}
