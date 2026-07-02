<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class DBShippingSettingsAdapter
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
class DBShippingSettingsAdapter extends AbstractShippingSettingsAdapter {
	/**
	 * Parses the already validated input data and maps the provided shipping rates into services.
	 *
	 * @param array $data Validated data.
	 */
	protected function map_gla_data( array $data ): void {
		$this->map_db_rates( $data['db_rates'] );
	}

	/**
	 * Validates the input array provided to this class.
	 *
	 * @param array $data
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	protected function validate_gla_data( array $data ): void {
		parent::validate_gla_data( $data );

		if ( empty( $data['db_rates'] ) || ! is_array( $data['db_rates'] ) ) {
			throw new InvalidValue( 'The value of "db_rates" must be a non empty associated array of shipping rates.' );
		}
	}

	/**
	 * Map the shipping rates stored for each country in DB to shipping services.
	 *
	 * @param array[] $db_rates
	 *
	 * @return void
	 */
	protected function map_db_rates( array $db_rates ): void {
		foreach ( $db_rates as ['country' => $country, 'rate' => $rate, 'options' => $options] ) {
			// No negative rates.
			if ( $rate < 0 ) {
				continue;
			}

			$service = $this->create_shipping_service( $country, (float) $rate );

			if ( isset( $options['free_shipping_threshold'] ) ) {
				$minimum_order_value = (float) $options['free_shipping_threshold'];

				if ( $rate > 0 ) {
					// Add a conditional free-shipping service if the current rate is not free.
					$this->services[] = $this->create_conditional_free_shipping_service( $country, $minimum_order_value );
				} else {
					// Set the minimum order value if the current rate is free.
					$service['minimumOrderValue'] = $this->create_price( $minimum_order_value );
				}
			}

			$this->services[] = $service;
		}
	}

	/**
	 * Create a shipping service.
	 *
	 * @param string $country
	 * @param float  $rate
	 *
	 * @return array
	 */
	protected function create_shipping_service( string $country, float $rate ): array {
		$unique = sprintf( '%04x', wp_rand( 0, 0xffff ) );

		return [
			'serviceName'       => sprintf(
				/* translators: %1 is a random 4-digit string, %2 is the rate, %3 is the currency, %4 is the country code */
				__( '[%1$s] Google for WooCommerce generated service - %2$s %3$s to %4$s', 'google-listings-and-ads' ),
				$unique,
				$rate,
				$this->currency,
				$country
			),
			'active'            => true,
			// One service per country; deliveryCountries is an array as MAPI requires.
			'deliveryCountries' => [ $country ],
			'currencyCode'      => $this->currency,
			'deliveryTime'      => $this->get_delivery_time( $country ),
			'shipmentType'      => 'DELIVERY',
			'rateGroups'        => [ $this->create_rate_group( $rate ) ],
		];
	}

	/**
	 * Create a single flat-rate rate group.
	 *
	 * @param float $rate
	 *
	 * @return array
	 */
	protected function create_rate_group( float $rate ): array {
		// No name: keep the rate-group shape consistent with the other adapters
		// (WC / postcode / state), which do not set an optional display label.
		return [
			'singleValue' => [ 'flatRate' => $this->create_price( $rate ) ],
		];
	}

	/**
	 * Create a free shipping service conditional on a minimum order value.
	 *
	 * @param string $country
	 * @param float  $minimum_order_value
	 *
	 * @return array
	 */
	protected function create_conditional_free_shipping_service( string $country, float $minimum_order_value ): array {
		$service                      = $this->create_shipping_service( $country, 0 );
		$service['minimumOrderValue'] = $this->create_price( $minimum_order_value );

		return $service;
	}
}
