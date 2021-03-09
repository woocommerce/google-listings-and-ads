<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery as RateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery as TimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Google_Service_ShoppingContent as ShoppingService;
use Google_Service_ShoppingContent_AccountTax as AccountTax;
use Google_Service_ShoppingContent_AccountTaxTaxRule as TaxRule;
use Google_Service_ShoppingContent_DeliveryTime as DeliveryTime;
use Google_Service_ShoppingContent_Price as Price;
use Google_Service_ShoppingContent_RateGroup as RateGroup;
use Google_Service_ShoppingContent_Service as Service;
use Google_Service_ShoppingContent_ShippingSettings as ShippingSettings;
use Google_Service_ShoppingContent_Value as Value;
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

		$settings = new ShippingSettings();
		$settings->setAccountId( $this->get_account_id() );

		$services = [];
		foreach ( $this->get_rates() as ['country' => $country, 'currency' => $currency, 'rate' => $rate] ) {
			$services[] = $this->create_main_service( $country, $currency, $rate );

			if ( $this->has_free_shipping_option() ) {
				$services[] = $this->create_free_shipping_service( $country, $currency );
			}
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
		return 'flat' === $this->get_settings()['shipping_rate'];
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

		return 'destination' === ( $this->get_options_object()->get( OptionsInterface::MERCHANT_CENTER )['tax_rate'] ?? 'destination' );
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
	protected function get_rates(): array {
		$rate_query = $this->container->get( RateQuery::class );

		return $rate_query->get_results();
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
	 * Create the array of rate groups for the service.
	 *
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return array
	 */
	protected function create_rate_groups( string $currency, $rate ): array {
		return [ $this->create_rate_group_object( $currency, $rate ) ];
	}

	/**
	 * Create a rate group object for the shopping settings.
	 *
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return RateGroup
	 */
	protected function create_rate_group_object( string $currency, $rate ): RateGroup {
		$price = new Price();
		$price->setCurrency( $currency );
		$price->setValue( $rate );

		$value = new Value();
		$value->setFlatRate( $price );

		$rate_group = new RateGroup();
		$rate_group->setSingleValue( $value );
		$rate_group->setName(
			sprintf(
				/* translators: %1 is the shipping rate, %2 is the currency (e.g. USD) */
				__( 'Flat rate - %1$s %2$s', 'google-listings-and-ads' ),
				$rate,
				$currency
			)
		);

		return $rate_group;
	}

	/**
	 * Determine whether free shipping is offered.
	 *
	 * @return bool
	 */
	protected function has_free_shipping_option(): bool {
		return boolval( $this->get_settings()['offers_free_shipping'] ?? false );
	}

	/**
	 * Get the free shipping minimum order value.
	 *
	 * @return int
	 */
	protected function get_free_shipping_minimum(): int {
		return intval( $this->get_settings()['free_shipping_threshold'] );
	}

	/**
	 * @return OptionsInterface
	 */
	protected function get_options_object(): OptionsInterface {
		return $this->container->get( OptionsInterface::class );
	}

	/**
	 * Create the main shipping service object.
	 *
	 * @param string $country
	 * @param string $currency
	 * @param mixed  $rate
	 *
	 * @return Service
	 */
	protected function create_main_service( string $country, string $currency, $rate ): Service {
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

		$service->setRateGroups( $this->create_rate_groups( $currency, $rate ) );

		$times = $this->get_times();
		if ( array_key_exists( $country, $times ) ) {
			$service->setDeliveryTime( $this->create_time_object( intval( $times[ $country ] ) ) );
		}

		return $service;
	}

	/**
	 * Create a free shipping service.
	 *
	 * @param string $country
	 * @param string $currency
	 *
	 * @return Service
	 */
	protected function create_free_shipping_service( string $country, string $currency ): Service {
		$price = new Price();
		$price->setValue( $this->get_free_shipping_minimum() );
		$price->setCurrency( $currency );

		$service = $this->create_main_service( $country, $currency, 0 );
		$service->setMinimumOrderValue( $price );

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
	 * @return ShoppingService
	 */
	protected function get_shopping_service(): ShoppingService {
		return $this->container->get( ShoppingService::class );
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
	 * Get the array of settings for the Merchant Center.
	 *
	 * @return array
	 */
	protected function get_settings(): array {
		return $this->get_options_object()->get( OptionsInterface::MERCHANT_CENTER );
	}
}
