<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateFlat
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class ShippingRateFlat extends ShippingRate {

	/**
	 * @var float
	 */
	protected $rate;

	/**
	 * @var array
	 */
	protected $shipping_class_rates;

	/**
	 * ShippingRateFlat constructor.
	 *
	 * @param float $rate                 The shipping cost in WooCommerce store currency.
	 * @param array $shipping_class_rates A multidimensional array of shipping class rates. {
	 *     Array of shipping method arguments.
	 *
	 *     @type string $class The shipping class slug/id.
	 *     @type float  $rate  The cost of the shipping method for the class in WooCommerce store currency.
	 * }
	 */
	public function __construct( float $rate, array $shipping_class_rates = [] ) {
		$this->rate                 = $rate;
		$this->shipping_class_rates = $shipping_class_rates;
	}

	/**
	 * @return float
	 */
	public function get_rate(): float {
		return $this->rate;
	}

	/**
	 * @param float $rate
	 *
	 * @return ShippingRateFlat
	 */
	public function set_rate( float $rate ): ShippingRateFlat {
		$this->rate = $rate;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_shipping_class_rates(): array {
		return $this->shipping_class_rates;
	}

	/**
	 * @param array $shipping_class_rates
	 *
	 * @return ShippingRateFlat
	 */
	public function set_shipping_class_rates( array $shipping_class_rates ): ShippingRateFlat {
		$this->shipping_class_rates = $shipping_class_rates;

		return $this;
	}

	/**
	 * Specify data which should be serialized to JSON
	 */
	public function jsonSerialize() {
		$data = parent::jsonSerialize();

		$data['rate'] = $this->rate;

		return $data;
	}

	/**
	 * @return string
	 */
	public static function get_method(): string {
		return 'flat_rate';
	}

}
