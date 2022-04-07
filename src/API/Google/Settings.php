<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery as RateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery as TimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\AccountAddress;
use Google\Service\ShoppingContent\AccountTax;
use Google\Service\ShoppingContent\AccountTaxTaxRule as TaxRule;
use Google\Service\ShoppingContent\DeliveryTime;
use Google\Service\ShoppingContent\Price;
use Google\Service\ShoppingContent\RateGroup;
use Google\Service\ShoppingContent\Service;
use Google\Service\ShoppingContent\ShippingSettings;
use Google\Service\ShoppingContent\Value;
use Psr\Container\ContainerInterface;

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

		if ( $this->should_get_shipping_rates_from_woocommerce() ) {
			$rates = $this->get_shipping_rates_from_woocommerce();
		} else {
			$rates = $this->get_shipping_rates_from_database();
		}

		$times = $this->get_times();

		$this->update_shipping_rates( $rates, $times );
	}

	/**
	 * Submit the given shipping rates to Google.
	 *
	 * @param array $rates A multidimensional array of shipping rates and their properties.
	 * @param array $times An array of estimated shipping times (in days) set for shipping countries.
	 *
	 * @see ShippingZone::get_shipping_rates_for_country() for the format of the $rates array.
	 *
	 * @throws InvalidValue If no shipping time is found for a country.
	 *
	 * @since 1.12.0
	 */
	public function update_shipping_rates( array $rates, array $times ) {
		$settings = new ShippingSettings();
		$settings->setAccountId( $this->get_account_id() );

		$services = [];
		foreach ( $rates as ['country' => $country, 'method' => $method, 'currency' => $currency, 'rate' => $rate, 'options' => $options] ) {
			// No negative rates.
			if ( $rate < 0 ) {
				continue;
			}

			if ( ! array_key_exists( $country, $times ) ) {
				throw new InvalidValue( 'No estimated shipping time found for country: ' . $country );
			}

			$delivery_days = intval( $times[ $country ] );

			$service = $this->create_shipping_service( $country, $method, $currency, (float) $rate, $delivery_days, $options );

			if ( isset( $options['free_shipping_threshold'] ) ) {
				$minimum_order_value = (float) $options['free_shipping_threshold'];

				if ( 0 !== $rate ) {
					// Add a conditional free-shipping service if the current rate is not free.
					$services[] = $this->create_conditional_free_shipping_service( $country, $currency, $minimum_order_value, $delivery_days );
				} else {
					// Set the minimum order value if the current rate is free.
					$service->setMinimumOrderValue(
						new Price(
							[
								'value'    => $minimum_order_value,
								'currency' => $currency,
							]
						)
					);
				}
			}

			$services[] = $service;
		}

		$settings->setServices( $services );

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
	protected function should_get_shipping_rates_from_woocommerce(): bool {
		return 'automatic' === ( $this->get_settings()['shipping_rate'] ?? '' );
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
	protected function get_times(): array {
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
	 * @return array
	 */
	protected function get_shipping_rates_from_woocommerce(): array {
		/** @var TargetAudience $target_audience */
		$target_audience  = $this->container->get( TargetAudience::class );
		$target_countries = $target_audience->get_target_countries();
		/** @var ShippingZone $shipping_zone */
		$shipping_zone = $this->container->get( ShippingZone::class );

		$rates = [];
		foreach ( $target_countries as $country ) {
			$country_rates = $shipping_zone->get_shipping_rates_for_country( $country );
			$rates         = array_merge( $rates, $country_rates );
		}

		return $rates;
	}

	/**
	 * Create the DeliveryTime object.
	 *
	 * @param int $delivery_days
	 *
	 * @return DeliveryTime
	 */
	protected function create_time_object( int $delivery_days ): DeliveryTime {
		$time = new DeliveryTime();
		$time->setMinHandlingTimeInDays( 0 );
		$time->setMaxHandlingTimeInDays( 0 );
		$time->setMinTransitTimeInDays( $delivery_days );
		$time->setMaxTransitTimeInDays( $delivery_days );

		return $time;
	}

	/**
	 * Create a rate group object for the shopping settings.
	 *
	 * @param string   $currency
	 * @param float    $rate
	 * @param string   $method
	 * @param string[] $shipping_labels
	 *
	 * @return RateGroup
	 */
	protected function create_rate_group_object( string $currency, float $rate, string $method, array $shipping_labels = [] ): RateGroup {
		$price = new Price();
		$price->setCurrency( $currency );
		$price->setValue( $rate );

		$value = new Value();
		$value->setFlatRate( $price );

		$rate_group = new RateGroup();

		$rate_group->setSingleValue( $value );

		$name = sprintf(
		/* translators: %1 is the shipping method, %2 is the shipping rate, %3 is the currency (e.g. USD) */
			__( '%1$s - %2$s %3$s', 'google-listings-and-ads' ),
			str_replace( '_', ' ', ucwords( $method, '_' ) ), // Capitalize the shipping method name.
			$rate,
			$currency
		);

		if ( ! empty( $shipping_labels ) ) {
			$rate_group->setApplicableShippingLabels( $shipping_labels );
			$name .= ' (' . implode( ', ', $shipping_labels ) . ')';
		}

		$rate_group->setName( $name );

		return $rate_group;
	}

	/**
	 * @return OptionsInterface
	 */
	protected function get_options_object(): OptionsInterface {
		return $this->container->get( OptionsInterface::class );
	}

	/**
	 * Create a shipping service object.
	 *
	 * @param string     $country
	 * @param string     $method
	 * @param string     $currency
	 * @param float      $rate
	 * @param int        $delivery_days
	 * @param array|null $options
	 *
	 * @return Service
	 */
	protected function create_shipping_service( string $country, string $method, string $currency, float $rate, int $delivery_days, ?array $options = [] ): Service {
		$unique  = sprintf( '%04x', mt_rand( 0, 0xffff ) );
		$service = new Service();
		$service->setActive( true );
		$service->setDeliveryCountry( $country );
		$service->setCurrency( $currency );
		$service->setName(
			sprintf(
				/* translators: %1 is a random 4-digit string, %2 is the rate, %3 is the currency, %4 is the country code  */
				__( '[%1$s] Google Listings and Ads generated service - %2$s %3$s to %4$s', 'google-listings-and-ads' ),
				$unique,
				$rate,
				$currency,
				$country
			)
		);

		$rate_groups = [];
		// Create a rate group for each shipping class (if any).
		if ( is_array( $options ) && ! empty( $options['shipping_class_rates'] ) ) {
			foreach ( $options['shipping_class_rates'] as ['class' => $class, 'rate' => $class_rate] ) {
				$rate_groups[] = $this->create_rate_group_object(
					$currency,
					$class_rate,
					$method,
					[ $class ]
				);
			}
		}
		// Create a main rate group for the service.
		$rate_groups[] = $this->create_rate_group_object( $currency, $rate, $method );
		$service->setRateGroups( $rate_groups );

		$service->setDeliveryTime( $this->create_time_object( $delivery_days ) );

		return $service;
	}

	/**
	 * Create a free shipping service.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param float  $minimum_order_value
	 * @param int    $delivery_days
	 *
	 * @return Service
	 */
	protected function create_conditional_free_shipping_service( string $country, string $currency, float $minimum_order_value, int $delivery_days ): Service {
		$service = $this->create_shipping_service( $country, ShippingZone::METHOD_FREE, $currency, 0, $delivery_days );

		// Set the minimum order value to be eligible for free shipping.
		$service->setMinimumOrderValue(
			new Price(
				[
					'value'    => $minimum_order_value,
					'currency' => $currency,
				]
			)
		);

		return $service;
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
