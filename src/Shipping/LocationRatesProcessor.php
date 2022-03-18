<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationRatesProcessor
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since x.x.x
 */
class LocationRatesProcessor {

	/**
	 * Process the shipping rates data for output.
	 *
	 * @param LocationRate[] $location_rates Array of shipping rates belonging to a specific location.
	 *
	 * @return LocationRate[] Array of processed location rates.
	 */
	public function process( array $location_rates ): array {
		/** @var LocationRate[] $grouped_rates */
		$grouped_rates = [];

		// Group the rates by shipping method.
		foreach ( $location_rates as $location_rate ) {
			$shipping_rate = $location_rate->get_shipping_rate();

			$method = $shipping_rate::get_method();
			if ( ! isset( $grouped_rates[ $method ] ) || $this->should_rate_be_replaced( $shipping_rate, $grouped_rates[ $method ]->get_shipping_rate() ) ) {
				$grouped_rates[ $method ] = $location_rate;
			}
		}

		// If there is an unconditional free shipping rate available, ignore all other shipping rates and return only the free rate.
		if ( isset( $grouped_rates[ ShippingRateFree::get_method() ] ) ) {
			$free_shipping = $grouped_rates[ ShippingRateFree::get_method() ];
			if ( null === $free_shipping->get_shipping_rate()->get_threshold() ) {
				return [ $free_shipping ];
			}
		}

		return array_values( $grouped_rates );
	}


	/**
	 * Checks whether the existing shipping rate should be replaced with a more suitable one. Used when grouping the rates.
	 *
	 * @param ShippingRate $new_rate
	 * @param ShippingRate $existing_rate
	 *
	 * @return bool
	 */
	protected function should_rate_be_replaced( ShippingRate $new_rate, ShippingRate $existing_rate ): bool {
		$replace = false;

		if ( $new_rate instanceof ShippingRateFlat &&
			 $existing_rate instanceof ShippingRateFlat &&
			 $new_rate->get_rate() > $existing_rate->get_rate()
		) {
			$replace = true;
		} elseif ( $new_rate instanceof ShippingRateFree &&
				   $existing_rate instanceof ShippingRateFree &&
				   (float) $new_rate->get_threshold() > (float) $existing_rate->get_threshold()
		) {
			$replace = true;
		}

		return $replace;
	}

}
