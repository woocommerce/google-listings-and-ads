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
	 * Every currency mapped to a row's country gets its own service, so
	 * products priced in each currency find a currency-matching shipping
	 * service. A row's amount is used directly for its own currency's
	 * service; rows stored in the store currency are converted for each
	 * additional mapped currency, and a currency with no conversion path is
	 * left out with an error.
	 *
	 * @param array[] $db_rates
	 *
	 * @return void
	 */
	protected function map_db_rates( array $db_rates ): void {
		// Per-row currency drives the synced service so multi-market stores don't
		// have their secondary-market rates pushed in the primary store currency.
		// Legacy rows missing a currency are treated as store-currency rows.
		foreach ( $db_rates as $db_rate ) {
			$country = $db_rate['country'] ?? null;
			$rate    = $db_rate['rate'] ?? null;
			$options = $db_rate['options'] ?? [];

			if ( null === $country || null === $rate ) {
				continue;
			}

			// No negative rates.
			if ( $rate < 0 ) {
				continue;
			}

			// A country with a rate but no shipping time is left out with an
			// error, so one bad country cannot cancel the whole update.
			if ( ! $this->has_delivery_time( $country ) ) {
				$this->report_country_missing_delivery_time( $country );
				continue;
			}

			$row_currency = ! empty( $db_rate['currency'] ) ? $db_rate['currency'] : $this->currency;

			// A country without a mapped currency list keeps the row's own
			// currency driving its single service, exactly as before markets
			// carried multiple currencies.
			$service_currencies = isset( $this->country_currency_map[ $country ] )
				? $this->get_currencies_for_country( $country )
				: [ $row_currency ];

			foreach ( $service_currencies as $service_currency ) {
				$service_rate = $this->resolve_row_amount( (float) $rate, $row_currency, $service_currency, $country );

				if ( null === $service_rate ) {
					$this->report_country_missing_conversion( $country, $service_currency );
					continue;
				}

				$minimum_order_value = null;
				if ( isset( $options['free_shipping_threshold'] ) ) {
					$minimum_order_value = $this->resolve_row_amount( (float) $options['free_shipping_threshold'], $row_currency, $service_currency, $country );

					if ( null === $minimum_order_value ) {
						$this->report_country_missing_conversion( $country, $service_currency );
						continue;
					}
				}

				$service = $this->create_shipping_service( $country, $service_currency, $service_rate );

				if ( null !== $minimum_order_value ) {
					if ( $service_rate > 0 ) {
						// Add a conditional free-shipping service if the current rate is not free.
						$this->services[] = $this->create_conditional_free_shipping_service( $country, $service_currency, $minimum_order_value );
					} else {
						// Set the minimum order value if the current rate is free.
						$service['minimumOrderValue'] = $this->mapi_price( $minimum_order_value, $service_currency );
					}
				}

				$this->services[] = $service;
			}
		}
	}

	/**
	 * Resolves a row amount for a service currency: the amount as-is when the
	 * row is stored in the service currency, the converted amount when the row
	 * is stored in the store currency, and null when neither applies (there is
	 * no conversion path between two non-store currencies).
	 *
	 * @param float  $amount           Amount as stored on the row.
	 * @param string $row_currency     Currency the row is stored in.
	 * @param string $service_currency Currency of the service being built.
	 * @param string $country          Country the service is built for.
	 *
	 * @return float|null
	 */
	protected function resolve_row_amount( float $amount, string $row_currency, string $service_currency, string $country ): ?float {
		if ( $service_currency === $row_currency ) {
			return $amount;
		}

		if ( $row_currency !== $this->currency ) {
			return null;
		}

		return $this->convert_amount_for_service( $amount, $service_currency, $country );
	}

	/**
	 * Create a shipping service.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param float  $rate
	 *
	 * @return array
	 */
	protected function create_shipping_service( string $country, string $currency, float $rate ): array {
		$unique = sprintf( '%04x', wp_rand( 0, 0xffff ) );

		return [
			'serviceName'       => sprintf(
				/* translators: %1 is a random 4-digit string, %2 is the rate, %3 is the currency, %4 is the country code */
				__( '[%1$s] Google for WooCommerce generated service - %2$s %3$s to %4$s', 'google-listings-and-ads' ),
				$unique,
				$rate,
				$currency,
				$country
			),
			'active'            => true,
			// One service per country; deliveryCountries is an array as MAPI requires.
			'deliveryCountries' => [ $country ],
			'currencyCode'      => $currency,
			'deliveryTime'      => $this->get_delivery_time( $country ),
			'shipmentType'      => 'DELIVERY',
			'rateGroups'        => [ $this->create_rate_group( $rate, $currency ) ],
		];
	}

	/**
	 * Create a single flat-rate rate group.
	 *
	 * @param float  $rate
	 * @param string $currency
	 *
	 * @return array
	 */
	protected function create_rate_group( float $rate, string $currency ): array {
		// No name: keep the rate-group shape consistent with the other adapters
		// (WC / postcode / state), which do not set an optional display label.
		return [
			'singleValue' => [ 'flatRate' => $this->mapi_price( $rate, $currency ) ],
		];
	}

	/**
	 * Create a free shipping service conditional on a minimum order value.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param float  $minimum_order_value
	 *
	 * @return array
	 */
	protected function create_conditional_free_shipping_service( string $country, string $currency, float $minimum_order_value ): array {
		$service                      = $this->create_shipping_service( $country, $currency, 0 );
		$service['minimumOrderValue'] = $this->mapi_price( $minimum_order_value, $currency );

		return $service;
	}
}
