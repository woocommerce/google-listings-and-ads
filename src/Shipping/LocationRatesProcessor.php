<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Class LocationRatesProcessor
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
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
		foreach ( $location_rates as $location_rate ) {
			$shipping_rate = $location_rate->get_shipping_rate();

			$type = 'flat_rate';
			// If there are conditional free shipping rates, we need to group and compare them together.
			if ( $shipping_rate->is_free() && $shipping_rate->has_min_order_amount() ) {
				$type = 'conditional_free';
			}

			// Append the shipping class names to the type key to group and compare the class rates together.
			$classes = ! empty( $shipping_rate->get_applicable_classes() ) ? join( ',', $shipping_rate->get_applicable_classes() ) : '';
			$type   .= $classes;

			if ( ! isset( $grouped_rates[ $type ] ) || $this->should_rate_be_replaced( $shipping_rate, $grouped_rates[ $type ]->get_shipping_rate() ) ) {
				$grouped_rates[ $type ] = $location_rate;
			}
		}

		// Ignore the conditional free rate if there are no flat rate or if the existing flat rate is free.
		if ( ! isset( $grouped_rates['flat_rate'] ) || $grouped_rates['flat_rate']->get_shipping_rate()->is_free() ) {
			unset( $grouped_rates['conditional_free'] );
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
		return $new_rate->get_rate() > $existing_rate->get_rate() ||
			(float) $new_rate->get_min_order_amount() > (float) $existing_rate->get_min_order_amount();
	}
}
