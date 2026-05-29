<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleDatafeedService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Syncs Google Merchant Center datafeeds whenever a market is added, updated, or deleted.
 *
 * Each language-currency pair in a market must have a corresponding datafeed entry in MC
 * with the market's target countries so that products submitted with that feedLabel are
 * served in the correct countries.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 * @since   3.7.0
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
	 * @var JobRepository
	 */
	protected JobRepository $job_repository;

	/**
	 * Whether shipping sync has already been scheduled in this request.
	 *
	 * @var bool
	 */
	protected bool $already_scheduled = false;

	/**
	 * @param GoogleDatafeedService $datafeed_service
	 * @param MarketService         $market_service
	 * @param JobRepository         $job_repository
	 */
	public function __construct( GoogleDatafeedService $datafeed_service, MarketService $market_service, JobRepository $job_repository ) {
		$this->datafeed_service = $datafeed_service;
		$this->market_service   = $market_service;
		$this->job_repository   = $job_repository;
	}

	/**
	 * Register WordPress action hooks for market lifecycle events.
	 *
	 * @since 3.7.0
	 */
	public function register(): void {
		add_action( 'woocommerce_gla_market_added', [ $this, 'on_market_added' ], 10, 2 );
		add_action( 'woocommerce_gla_market_updated', [ $this, 'on_market_updated' ], 10, 2 );
		add_action( 'woocommerce_gla_market_deleted', [ $this, 'on_market_deleted' ], 10, 2 );
	}

	/**
	 * Ensure datafeed(s) exist for a newly added secondary market.
	 *
	 * @since 3.7.0
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function on_market_added( string $id, array $config ): void {
		$this->sync_market_datafeeds( $config );
		$this->maybe_schedule_shipping_sync();
	}

	/**
	 * Re-sync datafeed(s) when a market is updated.
	 *
	 * @since 3.7.0
	 *
	 * @param string $id
	 * @param array  $config
	 */
	public function on_market_updated( string $id, array $config ): void {
		$this->sync_market_datafeeds( $config );
		$this->maybe_schedule_shipping_sync();
	}

	/**
	 * Delete datafeed(s) whose language-currency pairs are no longer used by any market.
	 *
	 * @since 3.7.0
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

		$this->maybe_schedule_shipping_sync();
	}

	/**
	 * Schedule an UpdateShippingSettings job unless one has already been scheduled in this request.
	 *
	 * @since 3.7.0
	 */
	protected function maybe_schedule_shipping_sync(): void {
		if ( $this->already_scheduled ) {
			return;
		}

		$this->job_repository->get( UpdateShippingSettings::class )->schedule();
		$this->already_scheduled = true;
	}

	/**
	 * Ensure all market datafeeds exist with correct country targeting.
	 * Called to repair state after bulk changes or initial setup.
	 * Intentionally does not trigger a shipping sync — this is a datafeed-only repair operation.
	 *
	 * @since 3.7.0
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
	 * @since 3.7.0
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
	 * @since 3.7.0
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
