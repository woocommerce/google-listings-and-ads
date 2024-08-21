<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use WC_Shipping_Method;
use WC_Shipping_Zone;

defined( 'ABSPATH' ) || exit;

/**
 * Class ZoneMethodsParser
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 2.1.0
 */
class ZoneMethodsParser implements Service {

	use PluginHelper;

	public const METHOD_FLAT_RATE = 'flat_rate';
	public const METHOD_FREE      = 'free_shipping';

	/**
	 * @var WC
	 */
	private $wc;

	/**
	 * ZoneMethodsParser constructor.
	 *
	 * @param WC $wc
	 */
	public function __construct( WC $wc ) {
		$this->wc = $wc;
	}

	/**
	 * Parses the given shipping method and returns its properties if it's supported. Returns null otherwise.
	 *
	 * @param WC_Shipping_Zone $zone
	 *
	 * @return ShippingRate[] Returns an array of parsed shipping rates, or null if the shipping method is not supported.
	 */
	public function parse( WC_Shipping_Zone $zone ): array {
		$parsed_rates = [];
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			$parsed_rates = array_merge( $parsed_rates, $this->shipping_method_to_rates( $method ) );
		}

		return $parsed_rates;
	}

	/**
	 * Parses the given shipping method and returns its properties if it's supported. Returns null otherwise.
	 *
	 * @param object|WC_Shipping_Method $method
	 *
	 * @return ShippingRate[] Returns an array of parsed shipping rates, or empty if the shipping method is not supported.
	 */
	protected function shipping_method_to_rates( object $method ): array {
		$shipping_rates = [];
		switch ( $method->id ) {
			case self::METHOD_FLAT_RATE:
				$flat_rate            = $this->get_flat_rate_method_rate( $method );
				$shipping_class_rates = $this->get_flat_rate_method_class_rates( $method );

				// If the flat-rate method has no rate AND no shipping classes, we don't return it.
				if ( null === $flat_rate && empty( $shipping_class_rates ) ) {
					return [];
				}

				$shipping_rates[] = new ShippingRate( (float) $flat_rate );

				if ( ! empty( $shipping_class_rates ) ) {
					foreach ( $shipping_class_rates as ['class' => $class, 'rate' => $rate] ) {
						$shipping_rate = new ShippingRate( $rate );
						$shipping_rate->set_applicable_classes( [ $class ] );
						$shipping_rates[] = $shipping_rate;
					}
				}

				break;
			case self::METHOD_FREE:
				$shipping_rate = new ShippingRate( 0 );

				// Check if free shipping requires a minimum order amount.
				$requires = $method->get_option( 'requires' );
				if ( in_array( $requires, [ 'min_amount', 'either' ], true ) ) {
					$shipping_rate->set_min_order_amount( (float) $method->get_option( 'min_amount' ) );
				} elseif ( in_array( $requires, [ 'coupon', 'both' ], true ) ) {
					// We can't sync this method if free shipping requires a coupon.
					return [];
				}
				$shipping_rates[] = $shipping_rate;
				break;
			default:
				/**
				 * Filter the shipping rates for a shipping method that is not supported.
				 *
				 * @param ShippingRate[] $shipping_rates The shipping rates.
				 * @param object|WC_Shipping_Method $method The shipping method.
				 */
				return apply_filters(
					'woocommerce_gla_handle_shipping_method_to_rates',
					$shipping_rates,
					$method
				);
		}

		return $shipping_rates;
	}

	/**
	 * Get the flat-rate shipping method rate.
	 *
	 * @param object|WC_Shipping_Method $method
	 *
	 * @return float|null
	 */
	protected function get_flat_rate_method_rate( object $method ): ?float {
		$rate = null;

		$flat_cost = 0;
		$cost      = $this->convert_to_standard_decimal( (string) $method->get_option( 'cost' ) );
		// Check if the cost is a numeric value (and not null or a math expression).
		if ( is_numeric( $cost ) ) {
			$flat_cost = (float) $cost;
			$rate      = $flat_cost;
		}

		// Add the no class cost.
		$no_class_cost = $this->convert_to_standard_decimal( (string) $method->get_option( 'no_class_cost' ) );
		if ( is_numeric( $no_class_cost ) ) {
			$rate = $flat_cost + (float) $no_class_cost;
		}

		return $rate;
	}

	/**
	 * Get the array of options of the flat-rate shipping method.
	 *
	 * @param object|WC_Shipping_Method $method
	 *
	 * @return array A multidimensional array of shipping class rates. {
	 *     Array of shipping method arguments.
	 *
	 *     @type string $class The shipping class slug/id.
	 *     @type float  $rate  The cost of the shipping method for the class in WooCommerce store currency.
	 * }
	 */
	protected function get_flat_rate_method_class_rates( object $method ): array {
		$class_rates = [];

		$flat_cost = 0;
		$cost      = $this->convert_to_standard_decimal( (string) $method->get_option( 'cost' ) );
		// Check if the cost is a numeric value (and not null or a math expression).
		if ( is_numeric( $cost ) ) {
			$flat_cost = (float) $cost;
		}

		// Add shipping class costs.
		$shipping_classes = $this->wc->get_shipping_classes();
		foreach ( $shipping_classes as $shipping_class ) {
			$shipping_class_cost = $this->convert_to_standard_decimal( (string) $method->get_option( 'class_cost_' . $shipping_class->term_id ) );
			if ( is_numeric( $shipping_class_cost ) ) {
				// Add the flat rate cost to the shipping class cost.
				$class_rates[ $shipping_class->slug ] = [
					'class' => $shipping_class->slug,
					'rate'  => $flat_cost + (float) $shipping_class_cost,
				];
			}
		}

		return array_values( $class_rates );
	}
}
