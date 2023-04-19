<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery as RateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery as TimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountTax;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountTaxTaxRule as TaxRule;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Settings {

	use LocationIDTrait;

	/** @var ContainerInterface */
	protected $container;

	/**
	 * Settings constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Sync the shipping settings with Google.
	 */
	public function sync_shipping() {
		if ( ! $this->should_sync_shipping() ) {
			return;
		}

		$settings = $this->generate_shipping_settings();

		$this->get_shopping_service()->shippingsettings->update(
			$this->get_merchant_id(),
			$this->get_account_id(),
			$settings
		);
	}

	/**
	 * Whether we should synchronize settings with the Merchant Center
	 *
	 * @return bool
	 */
	protected function should_sync_shipping(): bool {
		$shipping_rate = $this->get_settings()['shipping_rate'] ?? '';
		$shipping_time = $this->get_settings()['shipping_time'] ?? '';
		return in_array( $shipping_rate, [ 'flat', 'automatic' ], true ) && 'flat' === $shipping_time;
	}

	/**
	 * Whether we should get the shipping settings from the WooCommerce settings.
	 *
	 * @return bool
	 *
	 * @since 1.12.0
	 */
	public function should_get_shipping_rates_from_woocommerce(): bool {
		return 'automatic' === ( $this->get_settings()['shipping_rate'] ?? '' );
	}

	/**
	 * Generate a ShippingSettings object for syncing the store shipping settings to Merchant Center.
	 *
	 * @return ShippingSettings
	 *
	 * @since 2.1.0
	 */
	protected function generate_shipping_settings(): ShippingSettings {
		$times = $this->get_shipping_times();

		/** @var WC $wc_proxy */
		$wc_proxy = $this->container->get( WC::class );
		$currency = $wc_proxy->get_woocommerce_currency();

		if ( $this->should_get_shipping_rates_from_woocommerce() ) {
			return new WCShippingSettingsAdapter(
				[
					'currency'          => $currency,
					'rates_collections' => $this->get_shipping_rates_collections_from_woocommerce(),
					'delivery_times'    => $times,
					'accountId'         => $this->get_account_id(),
				]
			);
		}

		return new DBShippingSettingsAdapter(
			[
				'currency'       => $currency,
				'db_rates'       => $this->get_shipping_rates_from_database(),
				'delivery_times' => $times,
				'accountId'      => $this->get_account_id(),
			]
		);
	}

	/**
	 * Get the current tax settings from the API.
	 *
	 * @return AccountTax
	 */
	public function get_taxes(): AccountTax {
		return $this->get_shopping_service()->accounttax->get(
			$this->get_merchant_id(),
			$this->get_account_id()
		);
	}

	/**
	 * Whether we should sync tax settings.
	 *
	 * This depends on the store being in the US
	 *
	 * @return bool
	 */
	protected function should_sync_taxes(): bool {
		if ( 'US' !== $this->get_store_country() ) {
			return false;
		}

		return 'destination' === ( $this->get_settings()['tax_rate'] ?? 'destination' );
	}

	/**
	 * Sync tax setting with Google.
	 */
	public function sync_taxes() {
		if ( ! $this->should_sync_taxes() ) {
			return;
		}

		$taxes = new AccountTax();
		$taxes->setAccountId( $this->get_account_id() );

		$tax_rule = new TaxRule();
		$tax_rule->setUseGlobalRate( true );
		$tax_rule->setLocationId( $this->get_state_id( $this->get_store_state() ) );
		$tax_rule->setCountry( $this->get_store_country() );

		$taxes->setRules( [ $tax_rule ] );

		$this->get_shopping_service()->accounttax->update(
			$this->get_merchant_id(),
			$this->get_account_id(),
			$taxes
		);
	}

	/**
	 * Get shipping time data.
	 *
	 * @return array
	 */
	protected function get_shipping_times(): array {
		static $times = null;

		if ( null === $times ) {
			$time_query = $this->container->get( TimeQuery::class );
			$times      = array_column( $time_query->get_results(), 'time', 'country' );
		}

		return $times;
	}

	/**
	 * Get shipping rate data.
	 *
	 * @return array
	 */
	protected function get_shipping_rates_from_database(): array {
		$rate_query = $this->container->get( RateQuery::class );

		return $rate_query->get_results();
	}

	/**
	 * Get shipping rate data from WooCommerce shipping settings.
	 *
	 * @return CountryRatesCollection[] Array of rates collections for each target country specified in settings.
	 */
	protected function get_shipping_rates_collections_from_woocommerce(): array {
		/** @var TargetAudience $target_audience */
		$target_audience  = $this->container->get( TargetAudience::class );
		$target_countries = $target_audience->get_target_countries();
		/** @var ShippingZone $shipping_zone */
		$shipping_zone = $this->container->get( ShippingZone::class );

		$rates = [];
		foreach ( $target_countries as $country ) {
			$location_rates    = $shipping_zone->get_shipping_rates_for_country( $country );
			$rates[ $country ] = new CountryRatesCollection( $country, $location_rates );
		}

		return $rates;
	}

	/**
	 * @return OptionsInterface
	 */
	protected function get_options_object(): OptionsInterface {
		return $this->container->get( OptionsInterface::class );
	}

	/**
	 * Get the Merchant ID
	 *
	 * @return int
	 */
	protected function get_merchant_id(): int {
		return $this->get_options_object()->get( OptionsInterface::MERCHANT_ID );
	}

	/**
	 * Get the account ID.
	 *
	 * @return int
	 */
	protected function get_account_id(): int {
		// todo: there are some cases where this might be different than the Merchant ID.
		return $this->get_merchant_id();
	}

	/**
	 * Get the Shopping Service object.
	 *
	 * @return ShoppingContent
	 */
	protected function get_shopping_service(): ShoppingContent {
		return $this->container->get( ShoppingContent::class );
	}

	/**
	 * Get the country for the store.
	 *
	 * @return string
	 */
	protected function get_store_country(): string {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		return $wc->get_wc_countries()->get_base_country();
	}

	/**
	 * Get the state for the store.
	 *
	 * @return string
	 */
	protected function get_store_state(): string {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		return $wc->get_wc_countries()->get_base_state();
	}

	/**
	 * Get the WooCommerce store physical address.
	 *
	 * @return AccountAddress
	 *
	 * @since 1.4.0
	 */
	public function get_store_address(): AccountAddress {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		$countries   = $wc->get_wc_countries();
		$postal_code = ! empty( $countries->get_base_postcode() ) ? $countries->get_base_postcode() : null;
		$locality    = ! empty( $countries->get_base_city() ) ? $countries->get_base_city() : null;
		$country     = ! empty( $countries->get_base_country() ) ? $countries->get_base_country() : null;
		$region      = ! empty( $countries->get_base_state() ) ? $countries->get_base_state() : null;

		$mc_address = new AccountAddress();
		$mc_address->setPostalCode( $postal_code );
		$mc_address->setLocality( $locality );
		$mc_address->setCountry( $country );

		if ( ! empty( $region ) && ! empty( $country ) ) {
			$mc_address->setRegion( $this->maybe_get_state_name( $region, $country ) );
		}

		$address   = ! empty( $countries->get_base_address() ) ? $countries->get_base_address() : null;
		$address_2 = ! empty( $countries->get_base_address_2() ) ? $countries->get_base_address_2() : null;
		$separator = ! empty( $address ) && ! empty( $address_2 ) ? "\n" : '';
		$address   = sprintf( '%s%s%s', $countries->get_base_address(), $separator, $countries->get_base_address_2() );
		if ( ! empty( $address ) ) {
			$mc_address->setStreetAddress( $address );
		}

		return $mc_address;
	}

	/**
	 * Check whether the address has errors
	 *
	 * @param AccountAddress $address to be validated.
	 *
	 * @return array
	 */
	public function wc_address_errors( AccountAddress $address ): array {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		$countries = $wc->get_wc_countries();

		$locale          = $countries->get_country_locale();
		$locale_settings = $locale[ $address->getCountry() ] ?? [];

		$fields_to_validate = [
			'address_1' => $address->getStreetAddress(),
			'city'      => $address->getLocality(),
			'country'   => $address->getCountry(),
			'postcode'  => $address->getPostalCode(),
		];

		return $this->validate_address( $fields_to_validate, $locale_settings );
	}

	/**
	 * Check whether the required address fields are empty
	 *
	 * @param array $address_fields to be validated.
	 * @param array $locale_settings locale settings
	 * @return array
	 */
	public function validate_address( array $address_fields, array $locale_settings ): array {
		$errors = array_filter(
			$address_fields,
			function ( $field ) use ( $locale_settings, $address_fields ) {
				$is_required = $locale_settings[ $field ]['required'] ?? true;
				return $is_required && empty( $address_fields[ $field ] );
			},
			ARRAY_FILTER_USE_KEY
		);

		return array_keys( $errors );
	}

	/**
	 * Return a state name.
	 *
	 * @param string $state_code State code.
	 * @param string $country    Country code.
	 *
	 * @return string
	 *
	 * @since 1.4.0
	 */
	protected function maybe_get_state_name( string $state_code, string $country ): string {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		$states = $country ? array_filter( (array) $wc->get_wc_countries()->get_states( $country ) ) : [];

		if ( ! empty( $states ) ) {
			$state_code = wc_strtoupper( $state_code );
			if ( isset( $states[ $state_code ] ) ) {
				return $states[ $state_code ];
			}
		}

		return $state_code;
	}

	/**
	 * Get the array of settings for the Merchant Center.
	 *
	 * @return array
	 */
	protected function get_settings(): array {
		$settings = $this->get_options_object()->get( OptionsInterface::MERCHANT_CENTER );
		return is_array( $settings ) ? $settings : [];
	}
}
