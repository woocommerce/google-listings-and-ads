<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountRegionsService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiAccountShippingSettingsService;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\CountryRatesCollection;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\AbstractShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\DBShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\GoogleAdapter\WCShippingSettingsAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Shipping\ShippingZone;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountAddress;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountTax;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\AccountTaxTaxRule as TaxRule;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Container used for:
 * - MarketService
 * - OptionsInterface
 * - ShippingRateQuery
 * - ShippingTimeQuery
 * - ShippingZone
 * - ShoppingContent
 * - WC
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class Settings implements ContainerAwareInterface {

	use ContainerAwareTrait;
	use LocationIDTrait;

	/**
	 * Return a set of formatted settings which can be used in tracking.
	 *
	 * @since 2.5.16
	 *
	 * @return array
	 */
	public function get_settings_for_tracking() {
		$settings = $this->get_settings();

		return [
			'shipping_rate'           => $settings['shipping_rate'] ?? '',
			'offers_free_shipping'    => (bool) ( $settings['offers_free_shipping'] ?? false ),
			'free_shipping_threshold' => (float) ( $settings['free_shipping_threshold'] ?? 0 ),
			'shipping_time'           => $settings['shipping_time'] ?? '',
			'tax_rate'                => $settings['tax_rate'] ?? '',
			'target_countries'        => join( ',', $this->get_target_countries() ),
		];
	}

	/**
	 * Sync the shipping settings with Google.
	 */
	public function sync_shipping() {
		if ( ! $this->should_sync_shipping() ) {
			return;
		}

		$adapter = $this->generate_shipping_settings();

		// Regions must exist before the settings that reference them are inserted.
		$this->sync_shipping_regions( $adapter->get_regions() );

		/** @var MapiAccountShippingSettingsService $shipping_service */
		$shipping_service = $this->container->get( MapiAccountShippingSettingsService::class );
		$shipping_service->insert_shipping_settings( [ 'services' => $adapter->get_services() ] );
	}

	/**
	 * Create or update the Merchant API regions referenced by the shipping settings.
	 *
	 * Regions replace the Content API's inline postalCodeGroups; they are created
	 * up front so the rate-group tables can reference them by id.
	 *
	 * @param array<string, array> $regions Map of region id to Region resource.
	 *
	 * @throws MerchantApiException If a region cannot be created or updated.
	 */
	protected function sync_shipping_regions( array $regions ): void {
		if ( empty( $regions ) ) {
			return;
		}

		/** @var MapiAccountRegionsService $regions_service */
		$regions_service = $this->container->get( MapiAccountRegionsService::class );

		foreach ( $regions as $region_id => $region ) {
			try {
				$regions_service->insert_region( (string) $region_id, $region );
			} catch ( MerchantApiException $e ) {
				// The Merchant API reports an already-existing region as a 400.
				if ( 400 !== $e->get_http_status() ) {
					do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
					throw $e;
				}

				$regions_service->update_region( (string) $region_id, $region, 'displayName,postalCodeArea' );
			}
		}
	}

	/**
	 * Whether we should synchronize settings with the Merchant Center.
	 *
	 * @return bool
	 */
	protected function should_sync_shipping(): bool {
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );

		return $market_service->has_syncable_markets();
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
	 * Generate the shipping settings adapter for syncing the store shipping settings to Merchant Center.
	 *
	 * Builds a `[ country => currency ]` map from every non-manual market and
	 * passes it into the chosen adapter so that each per-country shipping service
	 * gets that market's own currency rather than a single store-wide value.
	 *
	 * The adapter choice still follows the primary's rate mode (`automatic`
	 * → WC adapter; otherwise → DB adapter), matching pre-multi-market behaviour.
	 *
	 * @return AbstractShippingSettingsAdapter
	 *
	 * @since 2.1.0
	 */
	protected function generate_shipping_settings(): AbstractShippingSettingsAdapter {
		$times = $this->get_shipping_times();

		/** @var WC $wc_proxy */
		$wc_proxy = $this->container->get( WC::class );
		$currency = $wc_proxy->get_woocommerce_currency();

		$country_currency_map   = $this->build_country_currency_map();
		$country_exchange_rates = $this->build_country_exchange_rate_map();

		/** @var WPML $wpml */
		$wpml = $this->container->get( WPML::class );

		if ( $this->should_get_shipping_rates_from_woocommerce() ) {
			return new WCShippingSettingsAdapter(
				[
					'currency'               => $currency,
					'country_currency_map'   => $country_currency_map,
					'country_exchange_rates' => $country_exchange_rates,
					'wpml'                   => $wpml,
					'rates_collections'      => $this->get_shipping_rates_collections_from_woocommerce(),
					'delivery_times'         => $times,
					'accountId'              => $this->get_account_id(),
				]
			);
		}

		return new DBShippingSettingsAdapter(
			[
				'currency'               => $currency,
				'country_currency_map'   => $country_currency_map,
				'country_exchange_rates' => $country_exchange_rates,
				'wpml'                   => $wpml,
				'db_rates'               => $this->get_shipping_rates_from_database(),
				'delivery_times'         => $times,
				'accountId'              => $this->get_account_id(),
			]
		);
	}

	/**
	 * Map of country code to the market's fixed exchange rate, for markets that configure one.
	 *
	 * The REST contract lets a secondary market sync in a currency the site cannot otherwise
	 * produce, provided it sets a positive rate. Shipping needs the same rate as product prices so
	 * a market's services are generated in the currency its prices already use.
	 *
	 * @return array<string, float>
	 */
	protected function build_country_exchange_rate_map(): array {
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );

		$map = [];

		foreach ( $market_service->get_participating_markets() as $market_id => $market ) {
			if ( 'primary' === $market_id || 'manual' === ( $market['shipping_rate'] ?? null ) ) {
				continue;
			}

			$country = $market['country'] ?? null;
			$rate    = isset( $market['exchange_rate'] ) && is_numeric( $market['exchange_rate'] )
				? (float) $market['exchange_rate']
				: 0.0;

			if ( $country && $rate > 0 ) {
				$map[ $country ] = $rate;
			}
		}

		return $map;
	}

	/**
	 * Returns a `[ country => currency[] ]` map for every participating,
	 * non-manual market.
	 *
	 * Each primary target country carries the store currency plus the primary
	 * market's additional participating currencies; each secondary market's
	 * country carries that market's participating currencies. Every listed
	 * currency gets its own shipping service for the country, because Google
	 * requires the shipping currency to match the product price currency.
	 * Manual markets are skipped — they don't get an MC shipping service.
	 * Markets and currencies excluded from syncing while currency conversion
	 * is unavailable are skipped for the same reason.
	 *
	 * @return array<string, string[]>
	 */
	protected function build_country_currency_map(): array {
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );

		/** @var WC $wc_proxy */
		$wc_proxy       = $this->container->get( WC::class );
		$store_currency = $wc_proxy->get_woocommerce_currency();

		$map = [];

		$primary = $market_service->get_primary_market();

		if ( 'manual' !== ( $primary['shipping_rate'] ?? null ) ) {
			$primary_currencies = array_values(
				array_unique(
					array_merge(
						[ $store_currency ],
						$market_service->get_participating_currencies( $primary )
					)
				)
			);

			foreach ( (array) ( $primary['countries'] ?? [] ) as $country ) {
				$map[ $country ] = $primary_currencies;
			}
		}

		foreach ( $market_service->get_participating_markets() as $market_id => $market ) {
			if ( 'primary' === $market_id ) {
				continue;
			}
			if ( 'manual' === ( $market['shipping_rate'] ?? null ) ) {
				continue;
			}
			$country = $market['country'] ?? null;
			if ( ! $country ) {
				continue;
			}

			$currencies = $market_service->get_participating_currencies( $market );

			$map[ $country ] = ! empty( $currencies ) ? $currencies : [ $store_currency ];
		}

		return $map;
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
			$time_query = $this->container->get( ShippingTimeQuery::class );
			$times      = $time_query->get_all_shipping_times();
		}

		return $times;
	}

	/**
	 * Get shipping rate data.
	 *
	 * Rows belonging to secondary markets that are currently excluded from
	 * syncing (non-store currency while conversion is unavailable) are left
	 * out, so those countries get no Merchant Center shipping service while
	 * their markets sit out. All other rows pass through untouched.
	 *
	 * @return array
	 */
	protected function get_shipping_rates_from_database(): array {
		$rate_query = $this->container->get( ShippingRateQuery::class );
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );

		$excluded_countries = $market_service->get_excluded_market_countries();
		$rates              = $rate_query->get_results();

		if ( empty( $excluded_countries ) ) {
			return $rates;
		}

		return array_values(
			array_filter(
				$rates,
				function ( array $rate ) use ( $excluded_countries ): bool {
					return ! in_array( $rate['country'] ?? null, $excluded_countries, true );
				}
			)
		);
	}

	/**
	 * Get shipping rate data from WooCommerce shipping settings.
	 *
	 * Covers every country needing a Merchant Center shipping service: the
	 * primary market's target countries plus each non-manual secondary
	 * market's country. Secondary market countries are removed from the
	 * target audience when the market is added, so iterating the target
	 * audience alone would leave them without a shipping service.
	 *
	 * @return CountryRatesCollection[] Array of rates collections for each country needing a shipping service.
	 */
	protected function get_shipping_rates_collections_from_woocommerce(): array {
		/** @var MarketService $market_service */
		$market_service = $this->container->get( MarketService::class );
		$countries      = $market_service->get_shipping_sync_countries();
		/** @var ShippingZone $shipping_zone */
		$shipping_zone = $this->container->get( ShippingZone::class );

		$rates = [];
		foreach ( $countries as $country ) {
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
		return $this->container->get( WC::class )->get_base_country();
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
	public function maybe_get_state_name( string $state_code, string $country ): string {
		/** @var WC $wc */
		$wc = $this->container->get( WC::class );

		$states = $country ? array_filter( (array) $wc->get_wc_countries()->get_states( $country ) ) : [];

		if ( ! empty( $states ) ) {
			$upper_code = wc_strtoupper( $state_code );
			if ( isset( $states[ $upper_code ] ) ) {
				return $states[ $upper_code ];
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

	/**
	 * Return a list of target countries or all.
	 *
	 * @return array
	 */
	protected function get_target_countries(): array {
		$target_audience = $this->get_options_object()->get( OptionsInterface::TARGET_AUDIENCE );

		if ( isset( $target_audience['location'] ) && 'all' === $target_audience['location'] ) {
			return [ 'all' ];
		}

		return $target_audience['countries'] ?? [];
	}
}
