<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use JsonSerializable;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationRate
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class LocationRate implements JsonSerializable {

	/**
	 * @var ShippingLocation
	 */
	protected $location;

	/**
	 * @var ShippingRate
	 */
	protected $shipping_rate;

	/**
	 * LocationRate constructor.
	 *
	 * @param ShippingLocation $location
	 * @param ShippingRate     $shipping_rate
	 */
	public function __construct( ShippingLocation $location, ShippingRate $shipping_rate ) {
		$this->location      = $location;
		$this->shipping_rate = $shipping_rate;
	}

	/**
	 * @return ShippingLocation
	 */
	public function get_location(): ShippingLocation {
		return $this->location;
	}

	/**
	 * @param ShippingLocation $location
	 *
	 * @return LocationRate
	 */
	public function set_location( ShippingLocation $location ): LocationRate {
		$this->location = $location;

		return $this;
	}

	/**
	 * @return ShippingRate
	 */
	public function get_shipping_rate(): ShippingRate {
		return $this->shipping_rate;
	}

	/**
	 * @param ShippingRate $shipping_rate
	 *
	 * @return LocationRate
	 */
	public function set_shipping_rate( ShippingRate $shipping_rate ): LocationRate {
		$this->shipping_rate = $shipping_rate;

		return $this;
	}

	/**
	 * Specify data which should be serialized to JSON
	 */
	public function jsonSerialize() {
		$rate_serialized = $this->shipping_rate->jsonSerialize();

		return array_merge(
			$rate_serialized,
			[
				'country' => $this->location->get_country(),
			]
		);
	}
}
