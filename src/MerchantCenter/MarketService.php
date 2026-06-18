<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketService
 *
 * Centralises all CRUD logic for managing markets within the plugin.
 * Other parts of the application should depend on this service for all
 * operations needed to manage markets.
 *
 * The "primary" market is never stored in the Markets option — it is
 * synthesised from existing site settings (TargetAudience, MerchantCenter,
 * ShippingRateQuery). Writes to primary fan out to those underlying stores.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MarketService implements Service, OptionsAwareInterface, Registerable {

	use OptionsAwareTrait;

	/**
	 * @var TargetAudience
	 */
	protected TargetAudience $target_audience;

	/**
	 * @var ShippingRateQuery
	 */
	protected ShippingRateQuery $shipping_rate_query;

	/**
	 * @var ShippingTimeQuery
	 */
	protected ShippingTimeQuery $shipping_time_query;

	/**
	 * @var WC
	 */
	protected WC $wc;

	/**
	 * @var WPML
	 */
	protected WPML $wpml;

	/**
	 * MarketService constructor.
	 *
	 * @param TargetAudience    $target_audience
	 * @param ShippingRateQuery $shipping_rate_query
	 * @param ShippingTimeQuery $shipping_time_query
	 * @param WC                $wc
	 * @param WPML              $wpml
	 */
	public function __construct(
		TargetAudience $target_audience,
		ShippingRateQuery $shipping_rate_query,
		ShippingTimeQuery $shipping_time_query,
		WC $wc,
		WPML $wpml
	) {
		$this->target_audience     = $target_audience;
		$this->shipping_rate_query = $shipping_rate_query;
		$this->shipping_time_query = $shipping_time_query;
		$this->wc                  = $wc;
		$this->wpml                = $wpml;
	}

	/**
	 * Register the service.
	 *
	 * No WordPress hooks are needed for this pure data service.
	 */
	public function register(): void {}

	/**
	 * Returns all markets, keyed by ID with the synthesised primary always first.
	 *
	 * @return array[] Keyed by market ID ('primary', then secondary IDs).
	 */
	public function get_markets(): array {
		$stored    = $this->options->get( OptionsInterface::MARKETS );
		$secondary = is_array( $stored ) ? $stored : [];
		unset( $secondary['primary'] );

		$all_rates     = $this->shipping_rate_query->get_all_shipping_rates();
		$all_countries = $this->wc->get_countries();

		foreach ( $secondary as &$market ) {
			$country = $market['country'] ?? null;

			$market['free_shipping'] = ( $country && isset( $all_rates[ $country ]['free_shipping_threshold'] ) )
				? (float) $all_rates[ $country ]['free_shipping_threshold']
				: null;

			$market['countries'] = $country ? [ $country ] : [];
			$market['label']     = $country ? ( $all_countries[ $country ] ?? null ) : null;
		}
		unset( $market );

		return [ 'primary' => $this->get_primary_market() ] + $secondary;
	}

	/**
	 * Persists a new markets configuration.
	 *
	 * Strips any 'primary' key — primary is never stored in the Markets option.
	 *
	 * @param array[] $markets
	 */
	public function update_markets( array $markets ): void {
		unset( $markets['primary'] );
		$this->options->update( OptionsInterface::MARKETS, $markets );
	}

	/**
	 * Generates the default markets configuration from site settings.
	 *
	 * Returns a config-shape primary keyed by 'primary'.
	 *
	 * @return array[]
	 */
	public function build_default_markets(): array {
		$country = $this->target_audience->get_main_target_country();

		return [
			'primary' => [
				'country'    => $country,
				'language'   => [ $this->get_site_primary_language() ],
				'currency'   => [ $this->get_site_primary_currency() ],
				'feed_label' => $country,
			],
		];
	}

	/**
	 * Builds and returns the full response-ready primary market.
	 *
	 * Composes from TargetAudience, MerchantCenter options, site locale/currency,
	 * and existing shipping rate logic so every caller receives the complete object.
	 *
	 * @return array
	 */
	public function get_primary_market(): array {
		$defaults    = $this->build_default_markets()['primary'];
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return [
			'id'            => 'primary',
			'label'         => __( 'Primary Market', 'google-listings-and-ads' ),
			'countries'     => $this->target_audience->get_target_countries(),
			'country'       => $defaults['country'],
			'language'      => $defaults['language'],
			'currency'      => $defaults['currency'],
			'feed_label'    => $defaults['feed_label'],
			'shipping_rate' => $mc_settings['shipping_rate'] ?? null,
			'shipping_time' => $mc_settings['shipping_time'] ?? null,
			'free_shipping' => $this->get_primary_free_shipping_threshold(),
		];
	}

	/**
	 * Returns a single market by ID.
	 *
	 * @param string $id
	 *
	 * @return array|null The market config, or null if not found.
	 */
	public function get_market( string $id ): ?array {
		$markets = $this->get_markets();

		return $markets[ $id ] ?? null;
	}

	/**
	 * Adds a new market config to the store.
	 *
	 * Primary cannot be added — it is always synthesised from site settings.
	 *
	 * When a secondary market is added for a country, that country is removed
	 * from the primary feed's TargetAudience so it is not listed in both feeds.
	 *
	 * @param string $id
	 * @param array  $config
	 *
	 * @throws InvalidValue When $id is 'primary' or $config is invalid.
	 */
	public function add_market( string $id, array $config ): void {
		if ( 'primary' === $id ) {
			throw new InvalidValue(
				sprintf( 'The market ID "%s" is reserved and cannot be added.', $id )
			);
		}

		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( ! isset( $config['shipping_rate'] ) ) {
			$config['shipping_rate'] = $mc_settings['shipping_rate'] ?? 'flat';
		}

		if ( ! isset( $config['shipping_time'] ) ) {
			$config['shipping_time'] = $mc_settings['shipping_time'] ?? 'flat';
		}

		$config = $this->merge_language_currency_with_primary( $config );

		$this->validate_secondary_market_config( $config );

		$markets        = $this->get_stored_secondary_markets();
		$markets[ $id ] = $config;
		$this->options->update( OptionsInterface::MARKETS, $markets );

		if ( ! empty( $config['country'] ) ) {
			$this->remove_country_from_target_audience( $config['country'] );
		}
	}

	/**
	 * Updates values of an existing market.
	 *
	 * When $id is 'primary', writes fan out to the underlying settings stores
	 * (MERCHANT_CENTER, TARGET_AUDIENCE) rather than the Markets option.
	 * For secondary markets, validates the merged config (existing + incoming)
	 * to allow partial updates while maintaining required-key integrity.
	 *
	 * @param string $id
	 * @param array  $config
	 *
	 * @return array The updated market.
	 *
	 * @throws InvalidValue When a secondary market's merged config is invalid.
	 */
	public function update_market( string $id, array $config ): array {
		if ( 'primary' === $id ) {
			$this->update_primary_market_fanout( $config );

			return $this->get_market( $id );
		}

		$markets  = $this->get_stored_secondary_markets();
		$existing = $markets[ $id ] ?? [];
		$merged   = array_merge( $existing, $config );

		if ( array_key_exists( 'language', $config ) || array_key_exists( 'currency', $config ) ) {
			$merged = $this->merge_language_currency_with_primary(
				$merged,
				array_key_exists( 'language', $config ),
				array_key_exists( 'currency', $config )
			);
		}

		$this->validate_secondary_market_config( $merged );

		$markets[ $id ] = $merged;
		$this->options->update( OptionsInterface::MARKETS, $markets );

		return $this->get_market( $id );
	}

	/**
	 * Deletes a market from the markets config based on ID.
	 *
	 * Primary cannot be deleted. After removal, the market's country is
	 * restored to the primary feed's TargetAudience.
	 *
	 * @param string $id
	 *
	 * @throws InvalidValue When $id is 'primary'.
	 */
	public function delete_market( string $id ): void {
		if ( 'primary' === $id ) {
			throw new InvalidValue(
				sprintf( 'The market ID "%s" is reserved and cannot be deleted.', $id )
			);
		}

		$markets = $this->get_stored_secondary_markets();
		$country = $markets[ $id ]['country'] ?? null;

		unset( $markets[ $id ] );
		$this->options->update( OptionsInterface::MARKETS, $markets );

		if ( $country ) {
			$this->adopt_primary_rate_for_country( $country );
			$this->adopt_primary_time_for_country( $country );
			$this->restore_country_to_target_audience( $country );
		}

		/**
		 * Fires after a secondary market is deleted and its shipping data is synced back to the primary.
		 *
		 * @param string      $id      Market ID that was deleted.
		 * @param string|null $country ISO 3166-1 alpha-2 country code of the deleted market, or null if the market had no country.
		 */
		do_action( 'woocommerce_gla_market_deleted', $id, $country );
	}

	/**
	 * Returns true if a supported multilingual integration is active.
	 *
	 * @return bool
	 */
	public function has_multilingual_support(): bool {
		return $this->wpml->is_active();
	}

	/**
	 * Returns the store's active languages from the multilingual integration.
	 *
	 * @return array<int, array{code: string, label: string}>
	 */
	public function get_languages(): array {
		return $this->wpml->get_languages();
	}

	/**
	 * Returns the store's active currencies from the multilingual integration.
	 *
	 * @return array<int, array{code: string, symbol: string}>
	 */
	public function get_currencies(): array {
		return $this->wpml->get_currencies();
	}

	/**
	 * Returns the stored secondary markets from the Markets option.
	 *
	 * @return array[]
	 */
	private function get_stored_secondary_markets(): array {
		$stored  = $this->options->get( OptionsInterface::MARKETS, [] );
		$markets = is_array( $stored ) ? $stored : [];
		unset( $markets['primary'] );

		return $markets;
	}

	/**
	 * Fans out a primary market update to the underlying settings stores.
	 *
	 * @param array $config Partial config — only supplied keys are written.
	 */
	private function update_primary_market_fanout( array $config ): void {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
		$mc_updated  = false;

		if ( array_key_exists( 'shipping_rate', $config ) ) {
			$mc_settings['shipping_rate'] = $config['shipping_rate'];
			$mc_updated                   = true;
		}

		if ( array_key_exists( 'shipping_time', $config ) ) {
			$mc_settings['shipping_time'] = $config['shipping_time'];
			$mc_updated                   = true;
		}

		if ( $mc_updated ) {
			$this->options->update( OptionsInterface::MERCHANT_CENTER, $mc_settings );
		}

		if ( array_key_exists( 'countries', $config ) ) {
			$target_audience              = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$target_audience['countries'] = $config['countries'];
			$this->options->update( OptionsInterface::TARGET_AUDIENCE, $target_audience );
		}
	}

	/**
	 * Returns the free-shipping threshold for the primary market's country.
	 *
	 * @return float|null The threshold amount, or null when unset.
	 */
	private function get_primary_free_shipping_threshold(): ?float {
		$country = $this->target_audience->get_main_target_country();
		$rates   = $this->shipping_rate_query->get_all_shipping_rates();

		if ( isset( $rates[ $country ]['free_shipping_threshold'] ) ) {
			return (float) $rates[ $country ]['free_shipping_threshold'];
		}

		return null;
	}

	/**
	 * Validates that a secondary market config contains the required keys.
	 *
	 * @param array $config The config to validate (full or merged).
	 *
	 * @throws InvalidValue When a required key is missing or not a non-empty string.
	 */
	private function validate_secondary_market_config( array $config ): void {
		foreach ( [ 'country', 'feed_label' ] as $key ) {
			if ( empty( $config[ $key ] ) || ! is_string( $config[ $key ] ) ) {
				throw InvalidValue::is_empty( $key );
			}
		}

		foreach ( [ 'language', 'currency' ] as $key ) {
			if ( ! isset( $config[ $key ] ) || ! is_array( $config[ $key ] ) ) {
				throw InvalidValue::is_empty( $key );
			}
		}
	}

	/**
	 * Prepends the site primary language and currency to request-supplied values.
	 *
	 * Omitted keys or empty arrays result in a single-element array containing
	 * only the site primary. Non-array values are rejected before merging.
	 *
	 * @param array $config
	 * @param bool  $merge_language Whether to merge the language field.
	 * @param bool  $merge_currency Whether to merge the currency field.
	 *
	 * @return array
	 *
	 * @throws InvalidValue When language or currency is present but not an array.
	 */
	private function merge_language_currency_with_primary(
		array $config,
		bool $merge_language = true,
		bool $merge_currency = true
	): array {
		if ( $merge_language ) {
			if ( array_key_exists( 'language', $config ) && ! is_array( $config['language'] ) ) {
				throw InvalidValue::is_empty( 'language' );
			}

			$language_extras    = isset( $config['language'] ) && is_array( $config['language'] ) ? $config['language'] : [];
			$config['language'] = array_values(
				array_unique(
					array_merge( [ $this->get_site_primary_language() ], $language_extras )
				)
			);
		}

		if ( $merge_currency ) {
			if ( array_key_exists( 'currency', $config ) && ! is_array( $config['currency'] ) ) {
				throw InvalidValue::is_empty( 'currency' );
			}

			$currency_extras    = isset( $config['currency'] ) && is_array( $config['currency'] ) ? $config['currency'] : [];
			$config['currency'] = array_values(
				array_unique(
					array_merge( [ $this->get_site_primary_currency() ], $currency_extras )
				)
			);
		}

		return $config;
	}

	/**
	 * Returns the site primary language code (ISO 639-1).
	 *
	 * Uses the WPML default language when multilingual support is active,
	 * otherwise falls back to the WordPress locale.
	 *
	 * @return string
	 */
	private function get_site_primary_language(): string {
		if ( $this->has_multilingual_support() ) {
			$from_wpml = $this->resolve_primary_language_from_wpml();

			if ( '' !== $from_wpml ) {
				return $from_wpml;
			}
		}

		return substr( get_locale(), 0, 2 );
	}

	/**
	 * Returns the site primary currency code (ISO 4217).
	 *
	 * Uses the WooCommerce store currency when it appears in the WPML currency
	 * list, otherwise the first WPML currency, when multilingual support is active.
	 *
	 * @return string
	 */
	private function get_site_primary_currency(): string {
		if ( $this->has_multilingual_support() ) {
			$from_wpml = $this->resolve_primary_currency_from_wpml();

			if ( '' !== $from_wpml ) {
				return $from_wpml;
			}
		}

		return get_woocommerce_currency();
	}

	/**
	 * Resolves the primary language from WPML integration data.
	 *
	 * @return string
	 */
	private function resolve_primary_language_from_wpml(): string {
		$languages    = $this->get_languages();
		$default_code = $this->wpml->get_default_language_code();

		foreach ( $languages as $language ) {
			if ( isset( $language['code'] ) && $default_code === $language['code'] ) {
				return $language['code'];
			}
		}

		return $languages[0]['code'] ?? '';
	}

	/**
	 * Resolves the primary currency from WPML integration data.
	 *
	 * @return string
	 */
	private function resolve_primary_currency_from_wpml(): string {
		$currencies     = $this->get_currencies();
		$store_currency = get_woocommerce_currency();

		foreach ( $currencies as $currency ) {
			if ( isset( $currency['code'] ) && $store_currency === $currency['code'] ) {
				return $currency['code'];
			}
		}

		return $currencies[0]['code'] ?? '';
	}

	/**
	 * Removes a country from the primary feed's TargetAudience countries list.
	 *
	 * Idempotent — no-op if the country is already absent.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 */
	private function remove_country_from_target_audience( string $country ): void {
		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );

		if ( empty( $target_audience['countries'] ) || ! is_array( $target_audience['countries'] ) ) {
			return;
		}

		$filtered = array_values(
			array_filter(
				$target_audience['countries'],
				function ( $c ) use ( $country ) {
					return $c !== $country;
				}
			)
		);

		if ( count( $filtered ) !== count( $target_audience['countries'] ) ) {
			$target_audience['countries'] = $filtered;
			$this->options->update( OptionsInterface::TARGET_AUDIENCE, $target_audience );
		}
	}

	/**
	 * Overwrites the deleted country's shipping rate row with the primary market's values.
	 *
	 * Reads the full ShippingRateTable once and filters in PHP. When both rows exist the target
	 * row is updated. When only the primary row exists the target row is inserted. When only the
	 * target row exists it is deleted so the existing shipping sync pipeline can recompute the
	 * country's rate on the next run. When neither row exists nothing happens.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code of the deleted secondary market.
	 */
	private function adopt_primary_rate_for_country( string $country ): void {
		$primary_country = $this->target_audience->get_main_target_country();
		$rows            = $this->shipping_rate_query->get_results();

		$primary_row  = null;
		$existing_row = null;

		foreach ( $rows as $row ) {
			if ( $row['country'] === $primary_country ) {
				$primary_row = $row;
			}
			if ( $row['country'] === $country ) {
				$existing_row = $row;
			}
		}

		if ( $primary_row ) {
			$primary_options = $primary_row['options'] ?? null;
			$data            = [
				'country'  => $country,
				'currency' => $primary_row['currency'],
				'rate'     => $primary_row['rate'],
				'options'  => is_array( $primary_options ) ? $primary_options : [],
			];

			if ( $existing_row ) {
				$this->shipping_rate_query->update( $data, [ 'id' => $existing_row['id'] ] );
			} else {
				$this->shipping_rate_query->insert( $data );
			}

			return;
		}

		if ( $existing_row ) {
			$this->shipping_rate_query->delete( 'country', $country );
		}
	}

	/**
	 * Overwrites the deleted country's shipping time row with the primary market's values.
	 *
	 * Same shape as adopt_primary_rate_for_country against ShippingTimeQuery / ShippingTimeTable.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code of the deleted secondary market.
	 */
	private function adopt_primary_time_for_country( string $country ): void {
		$primary_country = $this->target_audience->get_main_target_country();
		$rows            = $this->shipping_time_query->get_results();

		$primary_row  = null;
		$existing_row = null;

		foreach ( $rows as $row ) {
			if ( $row['country'] === $primary_country ) {
				$primary_row = $row;
			}
			if ( $row['country'] === $country ) {
				$existing_row = $row;
			}
		}

		if ( $primary_row ) {
			$data = [
				'country'  => $country,
				'time'     => $primary_row['time'],
				'max_time' => $primary_row['max_time'],
			];

			if ( $existing_row ) {
				$this->shipping_time_query->update( $data, [ 'id' => $existing_row['id'] ] );
			} else {
				$this->shipping_time_query->insert( $data );
			}

			return;
		}

		if ( $existing_row ) {
			$this->shipping_time_query->delete( 'country', $country );
		}
	}

	/**
	 * Restores a country to the primary feed's TargetAudience countries list.
	 *
	 * Idempotent — no-op if the country is already present.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 */
	private function restore_country_to_target_audience( string $country ): void {
		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );

		if ( ! isset( $target_audience['countries'] ) || ! is_array( $target_audience['countries'] ) ) {
			$target_audience['countries'] = [];
		}

		if ( ! in_array( $country, $target_audience['countries'], true ) ) {
			$target_audience['countries'][] = $country;
			$this->options->update( OptionsInterface::TARGET_AUDIENCE, $target_audience );
		}
	}
}
