<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

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
	 * Optional map of country code => ISO 4217 currency code used to override
	 * `$currency` on a per-country basis. Populated from configured markets.
	 *
	 * @var array<string, string>
	 */
	protected $country_currency_map = [];

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
	 * Returns the currency to use for a given country's shipping service,
	 * preferring the per-country mapping when supplied and falling back to the
	 * adapter's default `$currency` otherwise.
	 *
	 * @param string $country
	 * @return string
	 */
	protected function get_currency_for_country( string $country ): string {
		return $this->country_currency_map[ $country ] ?? $this->currency;
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
	}

	/**
	 * Parses the already validated input data and maps the provided shipping rates into services.
	 *
	 * @param array $data Validated data.
	 */
	abstract protected function map_gla_data( array $data ): void;
}
