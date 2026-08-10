<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractShippingSettingsAdapter
 *
 * Builds the Merchant API accounts.shippingSettings services (and the
 * accounts.regions they reference) from the store shipping data.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Shipping
 *
 * @since   2.1.0
 */
abstract class AbstractShippingSettingsAdapter {

	use MapiPriceTrait;

	/**
	 * @var string
	 */
	protected $currency;

	/**
	 * @var array
	 */
	protected $delivery_times;

	/**
	 * @var array The Merchant API service resources.
	 */
	protected $services = [];

	/**
	 * @var array<string, array> Map of region id to Merchant API Region resource.
	 */
	protected $regions = [];

	/**
	 * Optional map of country code => list of ISO 4217 currency codes used to
	 * override `$currency` on a per-country basis. Populated from configured
	 * markets; each listed currency gets its own shipping service.
	 *
	 * @var array<string, string[]>
	 */
	protected $country_currency_map = [];

	/**
	 * Optional map of country code => fixed exchange rate, from the market's configured rate.
	 * Used to convert store-currency amounts when no WPML conversion is available, mirroring how
	 * product prices treat the same rate.
	 *
	 * @var array<string, float>
	 */
	protected $country_exchange_rates = [];

	/**
	 * @var WPML|null WPML integration used to convert amounts for services in
	 *                a non-store currency.
	 */
	protected $wpml;

	/**
	 * AbstractShippingSettingsAdapter constructor.
	 *
	 * @param array $properties Used to seed this object's properties.
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	public function __construct( array $properties ) {
		$this->validate_gla_data( $properties );

		$this->currency             = $properties['currency'];
		$this->delivery_times       = $properties['delivery_times'];
		$this->country_currency_map = isset( $properties['country_currency_map'] ) && is_array( $properties['country_currency_map'] )
			? $properties['country_currency_map']
			: [];

		$this->country_exchange_rates = isset( $properties['country_exchange_rates'] ) && is_array( $properties['country_exchange_rates'] )
			? array_map( 'floatval', $properties['country_exchange_rates'] )
			: [];
		$this->wpml                   = isset( $properties['wpml'] ) && $properties['wpml'] instanceof WPML
			? $properties['wpml']
			: null;

		$this->map_gla_data( $properties );
	}

	/**
	 * Get the Merchant API shipping services.
	 *
	 * @return array
	 */
	public function get_services(): array {
		return $this->services;
	}

	/**
	 * Get the Merchant API regions referenced by the services, keyed by region id.
	 *
	 * @return array<string, array>
	 */
	public function get_regions(): array {
		return $this->regions;
	}

	/**
	 * Returns the currencies whose shipping services a given country needs,
	 * preferring the per-country mapping when supplied and falling back to a
	 * single-entry list with the adapter's default `$currency` otherwise.
	 * Every returned currency gets its own service for the country.
	 *
	 * @param string $country
	 * @return string[]
	 */
	protected function get_currencies_for_country( string $country ): array {
		$currencies = $this->country_currency_map[ $country ] ?? [];
		$currencies = array_values( array_filter( array_map( 'strval', (array) $currencies ) ) );

		return empty( $currencies ) ? [ $this->currency ] : $currencies;
	}

	/**
	 * Converts a store-currency amount into the given service currency.
	 *
	 * The store currency needs no conversion and is returned unchanged. Any other currency is
	 * converted via WPML, and when that is unavailable the country's fixed market exchange rate is
	 * used. Product prices apply that same rate as their own fallback, so a market priced by a fixed
	 * rate gets its shipping in the currency its prices are already in. Null when neither applies,
	 * so the caller can leave that currency's service out.
	 *
	 * @param float  $amount   Amount in the store currency.
	 * @param string $currency ISO 4217 currency code of the service.
	 * @param string $country  Country the service is built for, used to find its fixed rate.
	 *
	 * @return float|null
	 */
	protected function convert_amount_for_service( float $amount, string $currency, string $country ): ?float {
		if ( $currency === $this->currency ) {
			return $amount;
		}

		$converted = null !== $this->wpml ? $this->wpml->convert_amount( $amount, $currency ) : null;

		if ( null !== $converted ) {
			return $converted;
		}

		$rate = (float) ( $this->country_exchange_rates[ $country ] ?? 0.0 );

		return $rate > 0 ? $amount * $rate : null;
	}

	/**
	 * Reports a currency's shipping service left out for a country because its
	 * amounts cannot be converted into that currency.
	 *
	 * @param string $country
	 * @param string $currency
	 */
	protected function report_country_missing_conversion( string $country, string $currency ): void {
		do_action(
			'woocommerce_gla_error',
			sprintf(
				'Skipping the %1$s shipping service for country %2$s: the shipping amounts cannot be converted into that currency. Its Merchant Center shipping service is left out of the sync until currency conversion is available.',
				$currency,
				$country
			),
			__METHOD__
		);
	}

	/**
	 * Whether an estimated delivery time is configured for the given country.
	 *
	 * @param string $country
	 *
	 * @return bool
	 */
	protected function has_delivery_time( string $country ): bool {
		return array_key_exists( $country, $this->delivery_times );
	}

	/**
	 * Reports a country left out of the shipping settings because it has a
	 * shipping rate but no shipping time, naming the country and the missing
	 * data. Leaving it out removes that country's Merchant Center shipping
	 * service until the data is fixed, and keeps one bad country from
	 * cancelling the whole shipping settings update.
	 *
	 * @param string $country
	 */
	protected function report_country_missing_delivery_time( string $country ): void {
		do_action(
			'woocommerce_gla_error',
			sprintf(
				'Skipping the shipping service for country %s: it has a shipping rate but no shipping time. Its Merchant Center shipping service is left out of the sync until a shipping time is configured for it.',
				$country
			),
			__METHOD__
		);
	}

	/**
	 * Return the Merchant API deliveryTime for a given country in days.
	 *
	 * @param string $country
	 *
	 * @return array
	 *
	 * @throws InvalidValue If no delivery time can be found for the country.
	 */
	protected function get_delivery_time( string $country ): array {
		if ( ! array_key_exists( $country, $this->delivery_times ) ) {
			throw new InvalidValue( 'No estimated delivery time provided for country: ' . $country );
		}

		return [
			'minHandlingDays' => 0,
			'maxHandlingDays' => 0,
			'minTransitDays'  => (int) $this->delivery_times[ $country ]['time'],
			'maxTransitDays'  => (int) $this->delivery_times[ $country ]['max_time'],
		];
	}

	/**
	 * Build a Merchant API price for the store currency.
	 *
	 * Convenience wrapper over MapiPriceTrait::mapi_price() that supplies the
	 * adapter's configured currency.
	 *
	 * @param float $amount
	 *
	 * @return array
	 */
	protected function create_price( float $amount ): array {
		return $this->mapi_price( $amount, $this->currency );
	}

	/**
	 * Validates the input array provided to this class.
	 *
	 * @param array $data
	 *
	 * @throws InvalidValue When the required parameters are not provided, or they are invalid.
	 */
	protected function validate_gla_data( array $data ): void {
		if ( empty( $data['currency'] ) || ! is_string( $data['currency'] ) ) {
			throw new InvalidValue( 'The value of "currency" must be a non empty string.' );
		}
		if ( empty( $data['delivery_times'] ) || ! is_array( $data['delivery_times'] ) ) {
			throw new InvalidValue( 'The value of "delivery_times" must be a non empty array.' );
		}
	}

	/**
	 * Remove the extra data we added to the input array since the MC API doesn't expect them (and it will fail).
	 *
	 * @param array $data
	 */
	protected function unset_gla_data( array &$data ): void {
		unset( $data['currency'] );
		unset( $data['delivery_times'] );
		unset( $data['country_currency_map'] );
		unset( $data['country_exchange_rates'] );
		unset( $data['wpml'] );
	}

	/**
	 * Parses the already validated input data and maps the provided shipping rates into services.
	 *
	 * @param array $data Validated data.
	 */
	abstract protected function map_gla_data( array $data ): void;
}
