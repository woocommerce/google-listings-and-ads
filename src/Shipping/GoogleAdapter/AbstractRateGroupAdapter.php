<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Price;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\RateGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractRateGroupAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
abstract class AbstractRateGroupAdapter extends RateGroup {
	/**
	 * Initialize this object's properties from an array.
	 *
	 * @param array $properties Used to seed this object's properties.
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	public function mapTypes( $properties ) {
		if ( empty( $properties['currency'] ) || ! is_string( $properties['currency'] ) ) {
			throw new InvalidValue( 'The value of "currency" must be a non empty string.' );
		}
		if ( empty( $properties['location_rates'] ) || ! is_array( $properties['location_rates'] ) ) {
			throw new InvalidValue( 'The value of "location_rates" must be a non empty array.' );
		}

		$this->map_location_rates( $properties['location_rates'], $properties['currency'] );

		// Remove the extra data before calling the parent method since it doesn't expect them.
		unset( $properties['currency'] );
		unset( $properties['location_rates'] );

		parent::mapTypes( $properties );
	}

	/**
	 * @param float  $rate
	 * @param string $currency
	 *
	 * @return Value
	 */
	protected function create_value_object( float $rate, string $currency ): Value {
		$price = new Price(
			[
				'currency' => $currency,
				'value'    => $rate,
			]
		);

		return new Value( [ 'flatRate' => $price ] );
	}

	/**
	 * Map the location rates to the class properties.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency
	 *
	 * @return void
	 */
	abstract protected function map_location_rates( array $location_rates, string $currency ): void;
}
