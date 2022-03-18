<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use JsonSerializable;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRate
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
abstract class ShippingRate implements JsonSerializable {

	/**
	 * Specify data which should be serialized to JSON
	 */
	public function jsonSerialize() {
		return [
			'method' => static::get_method(),
		];
	}

	/**
	 * @return string
	 *
	 * @since x.x.x
	 */
	abstract public static function get_method(): string;

}
