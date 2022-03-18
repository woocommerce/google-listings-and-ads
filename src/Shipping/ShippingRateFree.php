<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateFree
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class ShippingRateFree extends ShippingRate {

	/**
	 * @var float|null
	 */
	protected $threshold;

	/**
	 * ShippingRateFree constructor.
	 *
	 * @param float|null $threshold The minimum order amount to qualify for this shipping rate.
	 */
	public function __construct( ?float $threshold = null ) {
		$this->threshold = $threshold;
	}

	/**
	 * @return float|null
	 */
	public function get_threshold(): ?float {
		return $this->threshold;
	}

	/**
	 * @param float|null $threshold
	 *
	 * @return ShippingRateFree
	 */
	public function set_threshold( ?float $threshold ): ShippingRateFree {
		$this->threshold = $threshold;

		return $this;
	}

	/**
	 * Specify data which should be serialized to JSON
	 */
	public function jsonSerialize() {
		$data = parent::jsonSerialize();

		if ( ! empty( $this->get_threshold() ) ) {
			$data['options']['free_shipping_threshold'] = $this->get_threshold();
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public static function get_method(): string {
		return 'free_shipping';
	}

}
