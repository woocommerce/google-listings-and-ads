<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class MarketService
 *
 * Centralises all CRUD logic for managing markets within the plugin.
 * Other parts of the application should depend on this service for all
 * operations needed to manage markets.
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
	 * MarketService constructor.
	 *
	 * @param TargetAudience $target_audience
	 */
	public function __construct( TargetAudience $target_audience ) {
		$this->target_audience = $target_audience;
	}

	/**
	 * Register the service.
	 *
	 * No WordPress hooks are needed for this pure data service.
	 */
	public function register(): void {}

	/**
	 * Returns all configured markets.
	 *
	 * Falls back to a single default market derived from the existing
	 * TargetAudience options so existing stores are unaffected.
	 *
	 * Each market is an associative array with keys:
	 *   - country   (string)  ISO 3166-1 alpha-2, e.g. "US"
	 *   - language  (string)  ISO 639-1, e.g. "en"
	 *   - currency  (string)  ISO 4217, e.g. "USD"
	 *   - feedLabel (string)  Google feedLabel, e.g. "US" or "US-ES"
	 *
	 * @return array[]
	 */
	public function get_markets(): array {
		$stored = $this->options->get( OptionsInterface::MARKETS );

		if ( ! empty( $stored ) && is_array( $stored ) ) {
			return $stored;
		}

		// Default: one market per target country using the site locale & currency.
		return $this->build_default_markets();
	}

	/**
	 * Persists a new markets configuration.
	 *
	 * @param array[] $markets
	 */
	public function update_markets( array $markets ): void {
		$this->options->update( OptionsInterface::MARKETS, $markets );
	}

	/**
	 * Generates the default markets configuration from site settings.
	 *
	 * Builds a single market using the primary target country, site locale,
	 * and WooCommerce currency to maintain back-compat with existing stores.
	 *
	 * @return array[]
	 */
	public function build_default_markets(): array {
		$country  = $this->target_audience->get_main_target_country();
		$language = substr( get_locale(), 0, 2 );
		$currency = get_woocommerce_currency();

		return [
			[
				'country'   => $country,
				'language'  => $language,
				'currency'  => $currency,
				'feedLabel' => $country,
			],
		];
	}

	/**
	 * Builds and returns the primary market based on site settings.
	 *
	 * Maintains back-compat with the current single-market plugin design.
	 *
	 * @return array
	 */
	public function get_primary_market(): array {
		return $this->build_default_markets()[0];
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
	 * TODO: The stable ID scheme should be defined when the Markets REST
	 * Controller (GOOWOO-560) is built.
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function add_market( string $id, array $config ): void {
		$markets        = $this->get_markets();
		$markets[ $id ] = $config;
		$this->update_markets( $markets );
	}

	/**
	 * Updates values of an existing market.
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function update_market( string $id, array $config ): void {
		$markets = $this->get_markets();

		if ( isset( $markets[ $id ] ) ) {
			$markets[ $id ] = array_merge( $markets[ $id ], $config );
		} else {
			$markets[ $id ] = $config;
		}

		$this->update_markets( $markets );
	}

	/**
	 * Deletes a market from the markets config based on ID.
	 *
	 * @param string $id
	 */
	public function delete_market( string $id ): void {
		$markets = $this->get_markets();
		unset( $markets[ $id ] );
		$this->update_markets( $markets );
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
}
