<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingSuggestionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
 */
class ShippingSuggestionService implements Service {

	/**
	 * @var ShippingZone
	 */
	protected $shipping_zone;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * ShippingSuggestionService constructor.
	 *
	 * @param ShippingZone $shipping_zone
	 * @param WC           $wc
	 */
	public function __construct( ShippingZone $shipping_zone, WC $wc ) {
		$this->shipping_zone = $shipping_zone;
		$this->wc            = $wc;
	}

	/**
	 * Get shipping rate suggestions.
	 *
	 * @param string $country_code
	 *
	 * @return array A multidimensional array of shipping rate suggestions. {
	 *     Array of shipping rate suggestion arguments.
	 *
	 *     @type string $country  The shipping country.
	 *     @type string $currency The suggested rate currency (this is the same as the store's currency).
	 *     @type float  $rate     The cost of the shipping method.
	 *     @type array  $options  Array of options for the shipping method.
	 * }
	 */
	public function get_suggestions( string $country_code ): array {
		$location_rates = $this->shipping_zone->get_shipping_rates_grouped_by_country( $country_code );

		$suggestions    = [];
		$free_threshold = null;
		foreach ( $location_rates as $location_rate ) {
			$serialized = $location_rate->jsonSerialize();

			// Check if there is a conditional free shipping rate (with minimum order amount).
			// We will set the minimum order amount as the free shipping threshold for other rates.
			$shipping_rate = $location_rate->get_shipping_rate();

			// Ignore rates with shipping classes.
			if ( ! empty( $shipping_rate->get_applicable_classes() ) ) {
				continue;
			}

			if ( $shipping_rate->is_free() && $shipping_rate->has_min_order_amount() ) {
				$free_threshold = $shipping_rate->get_min_order_amount();

				// Ignore the conditional free rate if there are other rates.
				if ( count( $location_rates ) > 1 ) {
					continue;
				}
			}

			// Add the store currency to each rate.
			$serialized['currency'] = $this->wc->get_woocommerce_currency();

			$suggestions[] = $serialized;
		}

		if ( null !== $free_threshold ) {
			// Set the free shipping threshold for all suggestions if there is one.
			foreach ( $suggestions as $key => $suggestion ) {
				$suggestion['options'] = [
					'free_shipping_threshold' => $free_threshold,
				];

				$suggestions[ $key ] = $suggestion;
			}
		}

		return $suggestions;
	}
}
