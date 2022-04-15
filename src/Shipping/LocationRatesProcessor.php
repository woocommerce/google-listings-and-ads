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
		// Return empty if there are no rates or the only shipping rate has a minimum order amount.
		if ( empty( $location_rates ) || $this->has_only_rates_with_min_order_amount( $location_rates ) ) {
			return [];
		}

		/** @var LocationRate[] $grouped_rates */
		$grouped_rates = [];
		foreach ( $location_rates as $location_rate ) {
			$shipping_rate = $location_rate->get_shipping_rate();

			$type = 'flat_rate';
			if ( $shipping_rate->is_free() ) {
				$type = 'free';

				// If there is an unconditional free shipping rate available, ignore all other shipping rates and return only the free rate.
				if ( ! $shipping_rate->has_min_order_amount() ) {
					return [ $location_rate ];
				}
			}

			if ( ! isset( $grouped_rates[ $type ] ) || $this->should_rate_be_replaced( $shipping_rate, $grouped_rates[ $type ]->get_shipping_rate() ) ) {
				$grouped_rates[ $type ] = $location_rate;
			}
		}

		return array_values( $grouped_rates );
	}

	/**
	 * Returns true if the only available location rates have minimum order amounts.
	 *
	 * @param LocationRate[] $location_rates
	 *
	 * @return bool
	 */
	protected function has_only_rates_with_min_order_amount( array $location_rates ): bool {
		foreach ( $location_rates as $location_rate ) {
			if ( ! $location_rate->get_shipping_rate()->has_min_order_amount() ) {
				return false;
			}
		}

		return true;
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
		return $new_rate->get_rate() > $existing_rate->get_rate() ||
			   (float) $new_rate->get_min_order_amount() > (float) $existing_rate->get_min_order_amount();
	}

}
