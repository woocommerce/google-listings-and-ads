<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Shipping_Method;
use WC_Shipping_Zone;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingZone
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since 1.9.0
 */
class ShippingZone implements Service {

	public const METHOD_FLAT_RATE = 'flat_rate';
	public const METHOD_FREE      = 'free_shipping';

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var array|null Array of shipping methods for each country.
	 */
	protected $methods_countries = null;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * ShippingZone constructor.
	 *
	 * @param WC           $wc
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( WC $wc, GoogleHelper $google_helper ) {
		$this->wc            = $wc;
		$this->google_helper = $google_helper;
	}

	/**
	 * Gets the shipping countries from the WooCommerce shipping zones.
	 *
	 * Note: This method only returns the countries that have at least one shipping method.
	 *
	 * @return string[]
	 */
	public function get_shipping_countries(): array {
		$this->parse_shipping_zones();

		$countries = array_keys( $this->methods_countries ?: [] );

		// Match the list of shipping countries with the list of Merchant Center supported countries.
		$countries = array_intersect( $countries, $this->google_helper->get_mc_supported_countries() );

		return array_values( $countries );
	}

	/**
	 * Returns the available shipping methods for a country.
	 *
	 * @param string $country_code
	 *
	 * @return array[] Returns an array of shipping methods for the given country.
	 *
	 * @see ShippingZone::parse_method() for the format of the returned array.
	 */
	public function get_shipping_methods_for_country( string $country_code ): array {
		$this->parse_shipping_zones();

		return ! empty( $this->methods_countries[ $country_code ] ) ? array_values( $this->methods_countries[ $country_code ] ) : [];
	}

	/**
	 * Returns the available shipping rates for a country.
	 *
	 * @param string $country_code
	 *
	 * @return array[] A multidimensional array of shipping rates for the given country. {
	 *     Array of shipping method arguments.
	 *
	 *     @type string $country  The shipping method country.
	 *     @type string $method   The shipping method ID.
	 *     @type string $currency The currency which the shipping rate is in. Defaults to the store currency.
	 *     @type float  $rate     The cost of the shipping method. Only if the method is flat-rate.
	 *     @type array  $options  Array of options for the shipping method (varies based on the method type). {
	 *         Array of options for the shipping method.
	 *
	 *         @type array[] $shipping_class_rates An array containing the shipping class names and their rates. Only if the method is flat-rate.
	 *         @type float   $free_shipping_threshold The minimum order amount required to use the shipping method. Only if the method is free shipping.
	 *
	 *     }
	 * }
	 */
	public function get_shipping_rates_for_country( string $country_code ): array {
		$methods = $this->get_shipping_methods_for_country( $country_code );

		// If the only shipping method is the free shipping method, return it as the only rate.
		if ( 1 === count( $methods ) && self::METHOD_FREE === $methods[0]['id'] ) {
			$rate = [
				'country'  => $country_code,
				'method'   => $methods[0]['id'],
				'currency' => $methods[0]['currency'],
				'rate'     => 0,
				'options'  => [],
			];
			if ( isset( $methods[0]['options']['min_amount'] ) ) {
				$rate['options']['free_shipping_threshold'] = $methods[0]['options']['min_amount'];
			}

			return [ $rate ];
		}

		$free_shipping = self::find_available_free_shipping_method( $methods );
		$rates         = [];
		foreach ( $methods as $method ) {
			// We process the free shipping method separately.
			if ( self::METHOD_FREE === $method['id'] ) {
				continue;
			}

			$rate = [
				'country'  => $country_code,
				'method'   => $method['id'],
				'currency' => $method['currency'],
				'rate'     => $method['options']['cost'] ?? 0,
				'options'  => [],
			];

			if ( null !== $free_shipping ) {
				if ( isset( $free_shipping['options']['min_amount'] ) ) {
					// If there is a free shipping method, and it has a minimum order amount, we set it as an option for all rates.
					$rate['options']['free_shipping_threshold'] = $free_shipping['options']['min_amount'];
				} else {
					// If there is a free shipping method without a minimum order amount, we set the rate to 0 to mark it as free.
					$rate['rate'] = 0;
				}
			}

			if ( ! empty( $method['options']['class_costs'] ) ) {
				// If there are shipping classes, we set the cost of each class as an option.
				$rate['options']['shipping_class_rates'] = [];
				foreach ( $method['options']['class_costs'] as $class_id => $cost ) {
					$rate['options']['shipping_class_rates'][] = [
						'class' => $class_id,
						'rate'  => $cost,
					];
				}
			}

			$rates[] = $rate;
		}

		return $rates;
	}

	/**
	 * Parses the WooCommerce shipping zones and maps the ones that are supported and enabled into the self::$methods_countries array.
	 */
	protected function parse_shipping_zones(): void {
		// Don't parse if already parsed.
		if ( null !== $this->methods_countries ) {
			return;
		}
		$this->methods_countries = [];

		foreach ( $this->wc->get_shipping_zones() as $zone ) {
			$zone = $this->wc->get_shipping_zone( $zone['zone_id'] );
			/**
			 * @var WC_Shipping_Method[] $methods
			 */
			$methods = $zone->get_shipping_methods();

			// Skip if no shipping methods.
			if ( empty( $methods ) ) {
				continue;
			}

			foreach ( $methods as $method ) {
				// Skip if the method is unsupported or disabled.
				if ( ! $method->is_enabled() || ! self::is_shipping_method_supported( $method->id ) ) {
					continue;
				}

				$method = $this->parse_method( $method );

				// Skip if method cannot be parsed.
				if ( null === $method ) {
					continue;
				}

				// Add the method to the list of methods for each country in the zone.
				foreach ( $this->get_shipping_countries_from_zone( $zone ) as $country ) {
					// Initialize the shipping methods array if it doesn't exist.
					$this->methods_countries[ $country ] = $this->methods_countries[ $country ] ?? [];

					// Add the method to the array of shipping methods for the country if it doesn't exist.
					if ( ! isset( $this->methods_countries[ $country ][ $method['id'] ] ) ||
						 self::should_method_be_replaced( $method, $this->methods_countries[ $country ][ $method['id'] ] )
					) {
						$this->methods_countries[ $country ][ $method['id'] ] = $method;
					}
				}
			}
		}
	}

	/**
	 * Checks whether the shipping method should be replaced with a more suitable one.
	 *
	 * @param array $method
	 * @param array $existing_method
	 *
	 * @return bool
	 */
	protected static function should_method_be_replaced( array $method, array $existing_method ): bool {
		if ( $method['id'] !== $existing_method['id'] ) {
			return false;
		}

		if (
			// If a flat-rate method already exists, we replace it with the one with the higher cost.
			self::does_method_have_higher_cost( $method, $existing_method ) ||
			// If a free-shipping method already exists, we replace it with the one with the higher required minimum order amount.
			self::does_method_have_higher_min_amount( $method, $existing_method )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether the given method has a higher cost than the existing method.
	 *
	 * @param array $method          A shipping method.
	 * @param array $existing_method Another shipping method to compare with.
	 *
	 * @return bool
	 */
	protected static function does_method_have_higher_cost( array $method, array $existing_method ): bool {
		if ( $method['id'] !== $existing_method['id'] ) {
			return false;
		}

		return self::METHOD_FLAT_RATE === $method['id'] && $method['options']['cost'] > $existing_method['options']['cost'];
	}

	/**
	 * Checks whether the given method has a minimum order amount higher than the existing method.
	 *
	 * @param array $method          A shipping method.
	 * @param array $existing_method Another shipping method to compare with.
	 *
	 * @return bool
	 */
	protected static function does_method_have_higher_min_amount( array $method, array $existing_method ): bool {
		if ( self::METHOD_FREE !== $method['id'] || $method['id'] !== $existing_method['id'] ) {
			return false;
		}

		// If a free shipping method already exists but doesn't have a minimum order amount, we replace it with the one with the one that has one.
		if ( isset( $method['options']['min_amount'] ) && ! isset( $existing_method['options']['min_amount'] ) ) {
			return true;
		}

		return isset( $method['options']['min_amount'] ) && isset( $existing_method['options']['min_amount'] ) && $method['options']['min_amount'] > $existing_method['options']['min_amount'];
	}

	/**
	 * Gets the list of countries defined in a shipping zone.
	 *
	 * @param WC_Shipping_Zone $zone
	 *
	 * @return string[] Array of country codes.
	 */
	protected function get_shipping_countries_from_zone( WC_Shipping_Zone $zone ): array {
		$countries = [];
		foreach ( $zone->get_zone_locations() as $location ) {
			switch ( $location->type ) {
				case 'country':
					$countries[ $location->code ] = $location->code;
					break;
				case 'continent':
					$countries = array_merge( $countries, $this->get_countries_from_continent( $location->code ) );
					break;
				case 'state':
					$country_code               = $this->get_country_of_state( $location->code );
					$countries[ $country_code ] = $country_code;
					break;
				default:
					break;
			}
		}

		return $countries;
	}

	/**
	 * Parses the given shipping method and returns its properties if it's supported. Returns null otherwise.
	 *
	 * @param object|WC_Shipping_Method $method
	 *
	 * @return array|null Returns an array with the parsed shipping method or null if the shipping method is not supported. {
	 *     Array of shipping method arguments.
	 *
	 *     @type string $id       The shipping method ID.
	 *     @type string $title    The user-defined title of the shipping method.
	 *     @type string $currency The currency which the shipping rate is in. Defaults to the store currency.
	 *     @type array  $options  Array of options for the shipping method (varies based on the method type). {
	 *         Array of options for the shipping method.
	 *
	 *         @type float   $cost The cost of the shipping method. Only if the method is flat-rate.
	 *         @type float[] $class_costs An array of costs for each shipping class (with class names used as array keys). Only if the method is flat-rate.
	 *         @type float   $min_amount The minimum order amount required to use the shipping method. Only if the method is free shipping.
	 *
	 *     }
	 * }
	 */
	protected function parse_method( object $method ): ?array {
		$parsed_method = [
			'id'       => $method->id,
			'title'    => $method->title,
			'currency' => $this->wc->get_woocommerce_currency(),
			'options'  => [],
		];

		switch ( $method->id ) {
			case self::METHOD_FLAT_RATE:
				$parsed_method['options'] = $this->get_flat_rate_method_options( $method );

				// If the flat-rate method has no cost AND no shipping classes, we don't return it.
				if ( ! isset( $parsed_method['options']['cost'] ) && ! isset( $parsed_method['options']['class_costs'] ) ) {
					return null;
				}

				break;
			case self::METHOD_FREE:
				// Check if free shipping requires a minimum order amount.
				$requires = $method->get_option( 'requires' );
				if ( in_array( $requires, [ 'min_amount', 'either' ], true ) ) {
					$parsed_method['options']['min_amount'] = (float) $method->get_option( 'min_amount' );
				} elseif ( in_array( $requires, [ 'coupon', 'both' ], true ) ) {
					// We can't sync this method if free shipping requires a coupon.
					return null;
				}
				break;
			default:
				// We don't support other shipping methods.
				return null;
		}

		return $parsed_method;
	}

	/**
	 * Get the array of options of the flat-rate shipping method.
	 *
	 * @param object $method
	 *
	 * @return array
	 */
	protected function get_flat_rate_method_options( object $method ): array {
		$options = [
			'cost' => null,
		];

		$flat_cost = 0;
		$cost      = $method->get_option( 'cost' );
		// Check if the cost is a numeric value (and not null or a math expression).
		if ( is_numeric( $cost ) ) {
			$flat_cost       = (float) $cost;
			$options['cost'] = $flat_cost;
		}

		// Add the no class cost.
		$no_class_cost = $method->get_option( 'no_class_cost' );
		if ( is_numeric( $no_class_cost ) ) {
			$options['cost'] = $flat_cost + (float) $no_class_cost;
		}

		// Add shipping class costs.
		$shipping_classes = $this->wc->get_shipping_classes();
		foreach ( $shipping_classes as $shipping_class ) {
			// Initialize the array if it doesn't exist.
			$options['class_costs'] = $options['class_costs'] ?? [];

			$shipping_class_cost = $method->get_option( 'class_cost_' . $shipping_class->term_id );
			if ( is_numeric( $shipping_class_cost ) ) {
				// Add the flat rate cost to the shipping class cost.
				$options['class_costs'][ $shipping_class->slug ] = $flat_cost + (float) $shipping_class_cost;
			}
		}

		return $options;
	}

	/**
	 * Gets the list of countries from a continent.
	 *
	 * @param string $continent_code
	 *
	 * @return string[] Returns an array of country codes with each country code used both as the key and value.
	 *                  For example: [ 'US' => 'US', 'DE' => 'DE' ].
	 */
	protected function get_countries_from_continent( string $continent_code ): array {
		$countries  = [];
		$continents = $this->wc->get_wc_countries()->get_continents();
		if ( isset( $continents[ $continent_code ] ) ) {
			$countries = $continents[ $continent_code ]['countries'];
			// Use the country code as array keys.
			$countries = array_combine( $countries, $countries );
		}

		return $countries;
	}

	/**
	 * Gets the country code of a state defined in WooCommerce shipping zone.
	 *
	 * @param string $state_code
	 *
	 * @return string
	 */
	protected function get_country_of_state( string $state_code ): string {
		$location_codes = explode( ':', $state_code );

		return $location_codes[0];
	}

	/**
	 * Checks whether the given shipping method is supported.
	 *
	 * @param string $method
	 *
	 * @return bool
	 */
	public static function is_shipping_method_supported( string $method ): bool {
		return in_array(
			$method,
			[
				self::METHOD_FLAT_RATE,
				self::METHOD_FREE,
			],
			true
		);
	}

	/**
	 * Finds and returns the free shipping method if it exists in the list of suggested shipping methods.
	 *
	 * @param array $methods
	 *
	 * @return array|null Array containing the free shipping method properties, or null if it does not exist.
	 */
	protected static function find_available_free_shipping_method( array $methods ): ?array {
		$free_shipping_method = array_filter(
			$methods,
			function ( $method ) {
				return self::METHOD_FREE === $method['id'];
			}
		);

		if ( empty( $free_shipping_method ) ) {
			return null;
		}

		return array_values( $free_shipping_method )[0];
	}
}
