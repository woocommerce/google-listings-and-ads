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
 * @since 2.1.0
 */
class ShippingRate implements JsonSerializable {

	/**
	 * @var float
	 */
	protected $rate;

	/**
	 * @var float|null
	 */
	protected $min_order_amount;

	/**
	 * @var array
	 */
	protected $applicable_classes = [];

	/**
	 * ShippingRate constructor.
	 *
	 * @param float $rate The shipping cost in store currency.
	 */
	public function __construct( float $rate ) {
		$this->rate = $rate;
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
	 * @return ShippingRate
	 */
	public function set_rate( float $rate ): ShippingRate {
		$this->rate = $rate;

		return $this;
	}

	/**
	 * Returns whether the shipping rate is free.
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		return 0.0 === $this->get_rate();
	}

	/**
	 * @return float|null
	 */
	public function get_min_order_amount(): ?float {
		return $this->min_order_amount;
	}

	/**
	 * @param float|null $min_order_amount
	 */
	public function set_min_order_amount( ?float $min_order_amount ): void {
		$this->min_order_amount = $min_order_amount;
	}

	/**
	 * Returns whether the shipping rate has a minimum order amount constraint.
	 *
	 * @return bool
	 */
	public function has_min_order_amount(): bool {
		return ! is_null( $this->get_min_order_amount() );
	}

	/**
	 * @return string[]
	 */
	public function get_applicable_classes(): array {
		return $this->applicable_classes;
	}

	/**
	 * @param string[] $applicable_classes
	 *
	 * @return ShippingRate
	 */
	public function set_applicable_classes( array $applicable_classes ): ShippingRate {
		$this->applicable_classes = $applicable_classes;

		return $this;
	}

	/**
	 * Specify data which should be serialized to JSON
	 */
	public function jsonSerialize(): array {
		return [
			'rate' => $this->get_rate(),
		];
	}
}
