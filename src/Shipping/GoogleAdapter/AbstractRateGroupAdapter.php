<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\LocationRate;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractRateGroupAdapter
 *
 * Builds a single Merchant API rate group (a main table keyed by region or
 * location) from a set of location rates.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
abstract class AbstractRateGroupAdapter {

	use MapiPriceTrait;

	/**
	 * @var array The Merchant API rate group resource.
	 */
	protected $rate_group = [];

	/**
	 * AbstractRateGroupAdapter constructor.
	 *
	 * @param array $properties Used to seed this object's properties.
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	public function __construct( array $properties ) {
		if ( empty( $properties['currency'] ) || ! is_string( $properties['currency'] ) ) {
			throw new InvalidValue( 'The value of "currency" must be a non empty string.' );
		}
		if ( empty( $properties['location_rates'] ) || ! is_array( $properties['location_rates'] ) ) {
			throw new InvalidValue( 'The value of "location_rates" must be a non empty array.' );
		}

		if ( ! empty( $properties['applicableShippingLabels'] ) ) {
			$this->rate_group['applicableShippingLabels'] = array_values( (array) $properties['applicableShippingLabels'] );
		}

		$this->map_location_rates( $properties['location_rates'], $properties['currency'] );
	}

	/**
	 * Get the Merchant API rate group.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return $this->rate_group;
	}

	/**
	 * Build a Merchant API flat-rate value.
	 *
	 * @param float  $rate
	 * @param string $currency
	 *
	 * @return array
	 */
	protected function create_value( float $rate, string $currency ): array {
		return [ 'flatRate' => $this->mapi_price( $rate, $currency ) ];
	}

	/**
	 * Map the location rates onto the rate group's main table.
	 *
	 * @param LocationRate[] $location_rates
	 * @param string         $currency
	 *
	 * @return void
	 */
	abstract protected function map_location_rates( array $location_rates, string $currency ): void;
}
