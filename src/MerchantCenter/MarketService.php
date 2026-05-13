<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
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
	 * MarketService constructor.
	 *
	 * @param TargetAudience    $target_audience
	 * @param ShippingRateQuery $shipping_rate_query
	 * @param ShippingTimeQuery $shipping_time_query
	 * @param WC                $wc
	 */
	public function __construct(
		TargetAudience $target_audience,
		ShippingRateQuery $shipping_rate_query,
		ShippingTimeQuery $shipping_time_query,
		WC $wc
	) {
		$this->target_audience     = $target_audience;
		$this->shipping_rate_query = $shipping_rate_query;
		$this->shipping_time_query = $shipping_time_query;
		$this->wc                  = $wc;
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
		$country  = $this->target_audience->get_main_target_country();
		$language = substr( get_locale(), 0, 2 );
		$currency = get_woocommerce_currency();

		return [
			'primary' => [
				'country'    => $country,
				'language'   => $language,
				'currency'   => $currency,
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
			$this->restore_country_to_target_audience( $country );
		}
	}

	/**
	 * Returns true if a supported multilingual integration is active.
	 *
	 * TODO: Connect WPML integration — GOOWOO-561.
	 *
	 * @return bool
	 */
	public function has_multilingual_support(): bool {
		return false;
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

		if ( array_key_exists( 'flat_shipping_min_time', $config ) || array_key_exists( 'flat_shipping_max_time', $config ) ) {
			$countries        = $config['countries'] ?? $this->target_audience->get_target_countries();
			$all_times        = $this->shipping_time_query->get_results();
			$times_by_country = array_column( $all_times, null, 'country' );

			foreach ( $countries as $country ) {
				$existing_time = $times_by_country[ $country ] ?? null;

				$new_min = array_key_exists( 'flat_shipping_min_time', $config ) && null !== $config['flat_shipping_min_time']
					? (int) $config['flat_shipping_min_time']
					: (int) ( $existing_time['time'] ?? 0 );

				$new_max = array_key_exists( 'flat_shipping_max_time', $config ) && null !== $config['flat_shipping_max_time']
					? (int) $config['flat_shipping_max_time']
					: (int) ( $existing_time['max_time'] ?? 0 );

				$data = [
					'country'  => $country,
					'time'     => $new_min,
					'max_time' => $new_max,
				];

				if ( $existing_time ) {
					$this->shipping_time_query->update( $data, [ 'id' => $existing_time['id'] ] );
				} elseif ( $new_min > 0 || $new_max > 0 ) {
					$this->shipping_time_query->insert( $data );
				}
			}
		}

		if ( array_key_exists( 'flat_shipping_rate', $config ) || array_key_exists( 'free_shipping', $config ) ) {
			$countries        = $config['countries'] ?? $this->target_audience->get_target_countries();
			$currency         = get_woocommerce_currency();
			$all_rates        = $this->shipping_rate_query->get_results();
			$rates_by_country = array_column( $all_rates, null, 'country' );

			foreach ( $countries as $country ) {
				$existing_rate    = $rates_by_country[ $country ] ?? null;
				$existing_options = $existing_rate ? (array) $existing_rate['options'] : [];
				$current_rate_val = $existing_rate ? (float) $existing_rate['rate'] : 0;

				$new_rate = array_key_exists( 'flat_shipping_rate', $config ) && null !== $config['flat_shipping_rate']
					? $config['flat_shipping_rate']
					: $current_rate_val;

				if ( array_key_exists( 'free_shipping', $config ) ) {
					if ( null !== $config['free_shipping'] && $config['free_shipping'] > 0 ) {
						$existing_options['free_shipping_threshold'] = (float) $config['free_shipping'];
					} else {
						unset( $existing_options['free_shipping_threshold'] );
					}
				}

				$data = [
					'country'  => $country,
					'currency' => $currency,
					'rate'     => $new_rate,
					'options'  => $existing_options,
				];

				if ( $existing_rate ) {
					$this->shipping_rate_query->update( $data, [ 'id' => $existing_rate['id'] ] );
				} elseif ( null !== $config['flat_shipping_rate'] ) {
					$this->shipping_rate_query->insert( $data );
				}
			}
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
		$required = [ 'country', 'language', 'currency', 'feed_label' ];

		foreach ( $required as $key ) {
			if ( empty( $config[ $key ] ) || ! is_string( $config[ $key ] ) ) {
				throw InvalidValue::is_empty( $key );
			}
		}
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
