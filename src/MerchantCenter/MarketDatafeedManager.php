<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleDatafeedService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Syncs Google Merchant Center datafeeds whenever a market is added, updated, or deleted.
 *
 * Each language-currency pair in a market must have a corresponding datafeed entry in MC
 * with the market's target countries so that products submitted with that feedLabel are
 * served in the correct countries.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */
class MarketDatafeedManager implements Service, Registerable {

	/**
	 * @var GoogleDatafeedService
	 */
	protected GoogleDatafeedService $datafeed_service;

	/**
	 * @var MarketService
	 */
	protected MarketService $market_service;

	/**
	 * @param GoogleDatafeedService $datafeed_service
	 * @param MarketService         $market_service
	 */
	public function __construct( GoogleDatafeedService $datafeed_service, MarketService $market_service ) {
		$this->datafeed_service = $datafeed_service;
		$this->market_service   = $market_service;
	}

	/**
	 * Register WordPress action hooks for market lifecycle events.
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_market_added', [ $this, 'on_market_added' ], 10, 2 );
		add_action( 'woocommerce_gla_market_updated', [ $this, 'on_market_updated' ], 10, 2 );
		add_action( 'woocommerce_gla_market_deleted', [ $this, 'on_market_deleted' ], 10, 2 );
	}

	/**
	 * Ensure datafeed(s) exist for a newly added secondary market.
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function on_market_added( string $id, array $config ): void {
		$this->sync_market_datafeeds( $config );
	}

	/**
	 * Re-sync datafeed(s) when a market is updated.
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function on_market_updated( string $id, array $config ): void {
		$this->sync_market_datafeeds( $config );
	}

	/**
	 * Delete datafeed(s) whose language-currency pairs are no longer used by any market.
	 *
	 * @param string $id
	 * @param array  $deleted_config Config of the market that was just removed.
	 */
	public function on_market_deleted( string $id, array $deleted_config ): void {
		$remaining_pairs = $this->get_all_active_feed_label_set();

		foreach ( $deleted_config['language'] ?? [] as $lang ) {
			foreach ( $deleted_config['currency'] ?? [] as $curr ) {
				$feed_label = "{$lang}-{$curr}";

				if ( ! isset( $remaining_pairs[ $feed_label ] ) ) {
					$this->datafeed_service->delete_by_feed_label( $feed_label );
				}
			}
		}
	}

	/**
	 * Ensure all market datafeeds exist with correct country targeting.
	 * Called to repair state after bulk changes or initial setup.
	 */
	public function ensure_all_market_datafeeds(): void {
		foreach ( $this->market_service->get_markets() as $market ) {
			$this->sync_market_datafeeds( $market );
		}
	}

	/**
	 * For each language × currency pair in a market, ensure a datafeed exists in MC
	 * with the market's target countries set on it.
	 *
	 * Primary markets carry a `countries` array (multiple countries); secondary markets
	 * carry a single `country` string.
	 *
	 * @param array $market
	 */
	protected function sync_market_datafeeds( array $market ): void {
		$target_countries = ! empty( $market['countries'] )
			? $market['countries']
			: ( ! empty( $market['country'] ) ? [ $market['country'] ] : [] );

		if ( empty( $target_countries ) ) {
			return;
		}

		foreach ( $market['language'] ?? [] as $lang ) {
			foreach ( $market['currency'] ?? [] as $curr ) {
				$this->datafeed_service->ensure_for_feed_label(
					"{$lang}-{$curr}",
					$lang,
					$target_countries
				);
			}
		}
	}

	/**
	 * Returns a set keyed by feedLabel of all language-currency pairs still active
	 * across all remaining markets.
	 *
	 * @return array<string, true>
	 */
	protected function get_all_active_feed_label_set(): array {
		$set = [];

		foreach ( $this->market_service->get_markets() as $market ) {
			foreach ( $market['language'] ?? [] as $lang ) {
				foreach ( $market['currency'] ?? [] as $curr ) {
					$set[ "{$lang}-{$curr}" ] = true;
				}
			}
		}

		return $set;
	}
}
