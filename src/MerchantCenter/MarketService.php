<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingRateQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\ShippingTimeQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Integration\WPML;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedLanguageProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\CleanupOrphanedMarketProductsJob;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateShippingSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Locale;

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
	 * Google Content API constraint on `feedLabel`: up to 20 characters,
	 * uppercase letters, digits and dashes only. Stored labels are capped at
	 * 13 characters so a derived per-language label (a dash plus a two-letter
	 * language code plus a dash plus a three-letter currency code) stays
	 * within the 20-character limit.
	 */
	private const FEED_LABEL_PATTERN = '/^[A-Z0-9-]{1,13}$/';

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
	 * @var JobRepository
	 */
	protected JobRepository $job_repository;

	/**
	 * @var ?array
	 */
	private ?array $cached_shipping_rates = null;

	/**
	 * MarketService constructor.
	 *
	 * @param TargetAudience    $target_audience
	 * @param ShippingRateQuery $shipping_rate_query
	 * @param ShippingTimeQuery $shipping_time_query
	 * @param WC                $wc
	 * @param WPML              $wpml
	 * @param JobRepository     $job_repository
	 */
	public function __construct(
		TargetAudience $target_audience,
		ShippingRateQuery $shipping_rate_query,
		ShippingTimeQuery $shipping_time_query,
		WC $wc,
		WPML $wpml,
		JobRepository $job_repository
	) {
		$this->target_audience     = $target_audience;
		$this->shipping_rate_query = $shipping_rate_query;
		$this->shipping_time_query = $shipping_time_query;
		$this->wc                  = $wc;
		$this->wpml                = $wpml;
		$this->job_repository      = $job_repository;
	}

	/**
	 * Register the service.
	 *
	 * Watches for changes in currency conversion availability so market
	 * participation changes propagate to Merchant Center without waiting for
	 * an unrelated sync.
	 */
	public function register(): void {
		add_action(
			'init',
			function () {
				$this->handle_conversion_availability_change();
			}
		);
	}

	/**
	 * Schedules a full product re-sync and a shipping settings sync when
	 * currency conversion availability changes (WPML activated or deactivated,
	 * or WCML multi-currency toggled).
	 *
	 * Markets priced in a non-store currency take part in syncing only while
	 * conversion is available, so an availability change re-labels what should
	 * exist in Merchant Center: the scheduled re-sync's stale-entry cleanup
	 * removes entries for markets that dropped out, and re-syncs markets that
	 * rejoined. Stores without such markets skip the scheduling entirely.
	 */
	private function handle_conversion_availability_change(): void {
		$available = $this->wpml->can_convert_currency() ? 'yes' : 'no';
		$stored    = $this->options->get( OptionsInterface::CURRENCY_CONVERSION_AVAILABLE );

		if ( $available === $stored ) {
			return;
		}

		$this->options->update( OptionsInterface::CURRENCY_CONVERSION_AVAILABLE, $available );

		// First run only records the state; there is no change to act on.
		if ( null === $stored ) {
			return;
		}

		if ( ! $this->has_markets_requiring_conversion() ) {
			return;
		}

		$this->schedule_shipping_sync();
		$this->job_repository->get( UpdateAllProducts::class )->schedule();
	}

	/**
	 * Whether any stored secondary market or the primary market carries a
	 * currency other than the store currency, i.e. depends on conversion
	 * availability for at least one of its feeds to take part.
	 *
	 * @return bool
	 */
	private function has_markets_requiring_conversion(): bool {
		foreach ( $this->get_stored_secondary_markets() as $market ) {
			foreach ( $this->get_market_currencies( $market ) as $currency ) {
				if ( get_woocommerce_currency() !== $currency ) {
					return true;
				}
			}
		}

		foreach ( $this->get_existing_primary_currencies() as $currency ) {
			if ( get_woocommerce_currency() !== $currency ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns all markets, keyed by ID with the synthesised primary always first.
	 *
	 * The shipping method (`shipping_rate`/`shipping_time`) is a single, store-wide
	 * setting held in the MERCHANT_CENTER option. Secondary markets keep their own
	 * snapshot of it from when they were created, but those copies can drift out of
	 * date (e.g. the merchant later switches the global rate to `manual`). Every
	 * returned market therefore reflects the current global values rather than its
	 * stored snapshot, so every consumer — the sync check, the currency map builder
	 * and the REST responses — reads a single source of truth. The same is done for
	 * `free_shipping` (see GOOWOO-698).
	 *
	 * @return array[] Keyed by market ID ('primary', then secondary IDs).
	 */
	public function get_markets(): array {
		$secondary = $this->get_secondary_markets_source();

		$all_rates       = $this->get_cached_shipping_rates();
		$all_countries   = $this->wc->get_countries();
		$is_flat_mode    = $this->is_flat_shipping_rate();
		$global_shipping = $this->global_shipping_method();

		foreach ( $secondary as &$market ) {
			$market  = $this->apply_site_locale_when_not_multilingual( $market );
			$country = $market['country'] ?? null;

			// Overwrite the stored snapshot with the live global shipping method so
			// no decision is ever made against a stale per-market copy.
			$market['shipping_rate'] = $global_shipping['shipping_rate'];
			$market['shipping_time'] = $global_shipping['shipping_time'];

			// DB rate rows are retained when the merchant switches modes so they
			// can be restored later, so the read boundary has to gate them.
			$market['free_shipping'] = ( $is_flat_mode && $country && isset( $all_rates[ $country ]['free_shipping_threshold'] ) )
				? (float) $all_rates[ $country ]['free_shipping_threshold']
				: null;

			$market['countries'] = $country ? [ $country ] : [];
			$market['label']     = $country ? ( $all_countries[ $country ] ?? null ) : null;
		}
		unset( $market );

		return [ 'primary' => $this->get_primary_market() ] + $secondary;
	}

	/**
	 * Returns the markets that currently take part in syncing to Google.
	 *
	 * Same as get_markets() minus secondary markets priced in a non-store
	 * currency while currency conversion is unavailable — their prices cannot
	 * be converted, and submitting store-currency prices against a
	 * market-currency service and feed label is rejected by Google. Stored
	 * market data is never modified; an excluded market reappears here the
	 * moment conversion is available again.
	 *
	 * Use get_markets() for anything user-facing (the Markets page must keep
	 * showing every saved market); use this for anything that feeds Google.
	 *
	 * @return array[] Keyed by market ID ('primary', then secondary IDs).
	 */
	public function get_participating_markets(): array {
		$markets = $this->get_markets();

		// Participation is judged on the stored rows, not on get_markets()
		// output: locale masking rewrites a market's currency to the store
		// currency when no multilingual integration is active, which would
		// hide the very currency mismatch the participation rule exists to
		// catch.
		$participating_ids = array_keys( $this->get_participating_secondary_markets() );

		foreach ( array_keys( $markets ) as $id ) {
			if ( 'primary' === $id ) {
				continue;
			}

			if ( ! in_array( $id, $participating_ids, true ) ) {
				unset( $markets[ $id ] );
			}
		}

		return $markets;
	}

	/**
	 * Returns the countries of stored secondary markets that are currently
	 * excluded from syncing (see get_participating_markets()). Used to keep
	 * those countries' shipping services out of the Merchant Center shipping
	 * settings while their markets sit out.
	 *
	 * @return string[]
	 */
	public function get_excluded_market_countries(): array {
		$countries = [];

		foreach ( $this->get_secondary_markets_source() as $market ) {
			if ( $this->is_market_participating( $market ) ) {
				continue;
			}

			if ( ! empty( $market['country'] ) ) {
				$countries[] = $market['country'];
			}
		}

		return array_values( array_unique( $countries ) );
	}

	/**
	 * Whether a secondary market currently takes part in syncing to Google.
	 *
	 * A market takes part when at least one of its currencies does: the store
	 * currency always takes part, and any other currency only while price
	 * conversion is available (WPML active with WCML multi-currency on). A
	 * market with a mix of convertible and unconvertible currencies keeps
	 * syncing the currencies it can.
	 *
	 * @param array $market The market config.
	 *
	 * @return bool
	 */
	private function is_market_participating( array $market ): bool {
		return ! empty( $this->get_participating_currencies( $market ) );
	}

	/**
	 * Returns the stored secondary markets that currently take part in syncing.
	 *
	 * @return array[]
	 */
	private function get_participating_secondary_markets(): array {
		return array_filter(
			$this->get_secondary_markets_source(),
			function ( array $market ): bool {
				return $this->is_market_participating( $market );
			}
		);
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
	 * Clears every market: the primary market's target audience and all
	 * stored secondary markets.
	 *
	 * Used when Merchant Center is disconnected, so no market configuration
	 * survives to collide with markets configured during a later onboarding —
	 * secondary markets are otherwise never cleared on disconnect, and can
	 * reappear as duplicates of a freshly chosen primary market once the
	 * merchant reconnects.
	 *
	 * The MERCHANT_CENTER option (which also carries the primary market's
	 * shipping method, language and currency) is deleted separately by the
	 * caller: it holds settings beyond markets, so its lifecycle belongs to
	 * whichever disconnect flow owns Merchant Center state, not to this
	 * service.
	 */
	public function reset_markets(): void {
		$this->options->delete( OptionsInterface::TARGET_AUDIENCE );
		$this->options->delete( OptionsInterface::MARKETS );
	}

	/**
	 * Returns the current secondary markets, choosing the source by shipping mode.
	 *
	 * Flat-rate markets are not persisted: a country becomes its own secondary market
	 * purely because its per-country shipping (rate/time/free-shipping) differs from the
	 * main target country's, so they are derived live from the shipping tables — the
	 * single source of truth for flat rates. Automatic and manual markets carry
	 * language/currency/exchange-rate that the shipping tables cannot express, so they
	 * stay persisted in the Markets option.
	 *
	 * @return array[] Keyed by market ID.
	 */
	private function get_secondary_markets_source(): array {
		if ( ! $this->is_flat_shipping_rate() ) {
			return $this->get_stored_secondary_markets();
		}

		$this->reconcile_orphaned_stored_markets();

		return $this->get_derived_flat_secondary_markets();
	}

	/**
	 * Fold any persisted secondary markets back into the flat model.
	 *
	 * Flat markets are derived from the shipping tables and never persisted, but a store that
	 * created markets under automatic/manual mode (add_market() removes their country from the
	 * target audience and stores them in the Markets option) — or under an earlier flat
	 * implementation — can still carry entries there. Once the global rate is flat those entries
	 * would be orphaned: invisible on the Markets page, not deletable, and no longer synced,
	 * because their country is in no market at all.
	 *
	 * Restoring each such country to the target audience makes it targeted again and re-derived
	 * from its own shipping rows (as its own market when they differ from the main country's,
	 * otherwise folded into the primary market), then the stale entries are cleared. The stored
	 * language/currency is intentionally dropped — flat rate carries none. Idempotent: a no-op
	 * once there are no stored markets.
	 *
	 * The removal mirrors the flat branch of delete_market(): a store may have synced products to
	 * Merchant Center under the removed markets' feed labels (e.g. GB-EN-GBP), so the same follow-up
	 * jobs are scheduled — CleanupOrphanedMarketProductsJob to drop the now-stale offers under those
	 * labels, UpdateAllProducts so the restored countries re-sync under their current flat feed
	 * labels, and the shipping settings sync when the global method is syncable.
	 */
	private function reconcile_orphaned_stored_markets(): void {
		$stored = $this->get_stored_secondary_markets();
		if ( empty( $stored ) ) {
			return;
		}

		// Capture every removed market's feed label variants before the Markets option is cleared,
		// so the cleanup job can target the offers still sitting under those labels in Merchant Center.
		// One group per market, flattened once after the loop.
		$label_variant_groups = [];

		foreach ( $stored as $market ) {
			if ( ! empty( $market['country'] ) ) {
				$this->restore_country_to_target_audience( (string) $market['country'] );
			}

			$feed_label = $market['feed_label'] ?? null;
			if ( $feed_label ) {
				$label_variant_groups[] = $this->get_market_feed_label_variants(
					(string) $feed_label,
					is_array( $market['language'] ?? null ) ? $market['language'] : [],
					$this->get_market_currencies( $market )
				);
			}
		}

		$orphaned_feed_labels = array_values( array_unique( array_merge( [], ...$label_variant_groups ) ) );

		$this->options->update( OptionsInterface::MARKETS, [] );

		if ( ! empty( $orphaned_feed_labels ) ) {
			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule( [ 'feed_labels' => $orphaned_feed_labels ] );
		}

		// The shipping method is global; when the flat global rate is syncable the restored
		// countries' shipping services are regenerated in Merchant Center.
		if ( $this->global_shipping_is_syncable() ) {
			$this->schedule_shipping_sync();
		}

		// Always resync: the restored countries need their products (re)submitted under the
		// current flat feed labels regardless of whether any old labels needed cleaning up.
		$this->job_repository->get( UpdateAllProducts::class )->schedule();

		// Deliberately no woocommerce_gla_market_deleted here: that action is for a user
		// deleting a single market via delete_market(). This is a system-driven bulk fold of
		// orphaned entries back into the flat model on read, not a user action, so firing a
		// per-market "deleted" event (and re-entering its listeners) would be misleading.
	}

	/**
	 * Derives secondary markets from the per-country flat-rate shipping tables.
	 *
	 * Before the Markets feature a store could configure a distinct flat rate and/or
	 * delivery time per country while every country lived in a single feed. The primary
	 * market represents one shipping configuration (the main target country's), so every
	 * other target country whose stored flat-rate shipping differs from the main
	 * country's is surfaced as its own editable secondary market. Countries that share
	 * the main country's shipping stay in the primary market. Nothing is persisted — the
	 * shipping tables remain the source of truth, so this stays correct as the merchant
	 * edits rates and needs no migration.
	 *
	 * Flat markets have no language/currency of their own (those fields are not offered
	 * for flat rate); the site defaults are attached so downstream feed-label and sync
	 * logic behaves exactly as it does for a non-multilingual store.
	 *
	 * @return array[] Keyed by market ID.
	 */
	private function get_derived_flat_secondary_markets(): array {
		$target_countries = $this->target_audience->get_target_countries();
		$main_country     = $this->target_audience->get_main_target_country();

		// Nothing to split when there is at most one country or no main country.
		if ( count( $target_countries ) < 2 || '' === $main_country ) {
			return [];
		}

		$rates = $this->get_cached_shipping_rates();
		$times = $this->shipping_time_query->get_all_shipping_times();
		$times = is_array( $times ) ? $times : [];

		$baseline_signature = $this->get_country_shipping_signature( $main_country, $rates, $times );

		$markets = [];
		foreach ( $target_countries as $country ) {
			if ( $country === $main_country ) {
				continue;
			}

			// A country with no per-country shipping row of its own has nothing distinct
			// to preserve, so it stays in the primary market (it inherits the primary's
			// shipping). Only a country with its own row that differs is split out.
			if ( ! isset( $rates[ $country ] ) && ! isset( $times[ $country ] ) ) {
				continue;
			}

			if ( $this->get_country_shipping_signature( $country, $rates, $times ) === $baseline_signature ) {
				continue;
			}

			$id = $this->generate_market_id( $country );

			$markets[ $id ] = [
				'country'    => $country,
				'feed_label' => strtoupper( $country ),
				'language'   => [ $this->get_site_primary_language() ],
				'currency'   => [ $this->get_site_primary_currency() ],
			];
		}

		return $markets;
	}

	/**
	 * Returns the primary market's country list.
	 *
	 * In flat mode the countries surfaced as their own secondary markets (those whose
	 * shipping differs from the main country's) are excluded so they are not listed in
	 * both the primary feed and their own market. In every other mode the full target
	 * audience is the primary market.
	 *
	 * @return string[]
	 */
	private function get_primary_market_countries(): array {
		$target_countries = $this->target_audience->get_target_countries();

		if ( ! $this->is_flat_shipping_rate() ) {
			return $target_countries;
		}

		$secondary_countries = array_column( $this->get_derived_flat_secondary_markets(), 'country' );

		return array_values( array_diff( $target_countries, $secondary_countries ) );
	}

	/**
	 * Builds a comparable signature of a country's stored flat-rate shipping (rate, free
	 * shipping threshold, min and max delivery time) so two countries can be tested for
	 * having identical shipping.
	 *
	 * @param string $country The country code.
	 * @param array  $rates   Shipping rates keyed by country (see ShippingRateQuery::get_all_shipping_rates()).
	 * @param array  $times   Shipping times keyed by country (see ShippingTimeQuery::get_all_shipping_times()).
	 *
	 * @return string
	 */
	private function get_country_shipping_signature( string $country, array $rates, array $times ): string {
		$rate = $rates[ $country ] ?? [];
		$time = $times[ $country ] ?? [];

		return (string) wp_json_encode(
			[
				'rate'          => isset( $rate['rate'] ) ? (string) $rate['rate'] : null,
				'free_shipping' => $rate['free_shipping_threshold'] ?? null,
				'time'          => isset( $time['time'] ) ? (string) $time['time'] : null,
				'max_time'      => isset( $time['max_time'] ) ? (string) $time['max_time'] : null,
			]
		);
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
		$mc_settings      = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
		$default_language = [ $this->get_site_primary_language() ];
		$default_currency = [ $this->get_site_primary_currency() ];

		return [
			'id'            => 'primary',
			'label'         => __( 'Primary Market', 'google-listings-and-ads' ),
			'countries'     => $this->get_primary_market_countries(),
			'country'       => null,
			'language'      => is_array( $mc_settings['language'] ?? null ) ? $mc_settings['language'] : $default_language,
			'currency'      => is_array( $mc_settings['currency'] ?? null ) ? $mc_settings['currency'] : $default_currency,
			'feed_label'    => null,
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
	 * Generates a market ID from a feed label.
	 *
	 * Centralises the ID-generation rule so any code path that creates a market
	 * (REST controller, batch import, migration, CLI) produces consistent IDs.
	 *
	 * @param string $feed_label The market's feed label.
	 *
	 * @return string The sanitised market ID.
	 *
	 * @throws InvalidValue When the generated ID equals the reserved 'primary' key.
	 */
	public function generate_market_id( string $feed_label ): string {
		$id = sanitize_title( $feed_label );

		if ( 'primary' === $id ) {
			throw new InvalidValue(
				sprintf( 'The feed label "%s" generates the reserved market ID "primary".', $feed_label )
			);
		}

		return $id;
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

		// Flat markets are derived from the shipping tables, not persisted (see
		// get_derived_flat_secondary_markets()). Adding one means targeting a new
		// country and seeding its shipping rows from the primary market; the merchant's
		// own rate/time values are saved separately through the shipping endpoints.
		if ( $this->is_flat_shipping_rate() ) {
			$country = $config['country'] ?? '';
			if ( '' === $country || ! is_string( $country ) ) {
				throw InvalidValue::is_empty( 'country' );
			}

			$this->restore_country_to_target_audience( $country );
			$this->extend_shipping_to_country( $country );

			if ( $this->global_shipping_is_syncable() ) {
				$this->schedule_shipping_sync();
			}

			$this->job_repository->get( UpdateAllProducts::class )->schedule();

			/** This action is documented in this method's persisted-market branch below. */
			do_action( 'woocommerce_gla_market_added', $id, array_merge( $config, $this->global_shipping_method() ) );

			return;
		}

		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( ! isset( $config['shipping_rate'] ) ) {
			$config['shipping_rate'] = $mc_settings['shipping_rate'] ?? 'flat';
		}

		if ( ! isset( $config['shipping_time'] ) ) {
			$config['shipping_time'] = $mc_settings['shipping_time'] ?? 'flat';
		}

		// The market form omits the language and currency fields for some
		// shipping methods and for stores without a multilingual integration,
		// so missing values fall back to the site defaults.
		$config = $this->apply_locale_defaults( $config );

		$this->validate_secondary_market_config( $config );

		// Recorded so delete_market() knows whether the country should rejoin
		// the primary market (it was moved out of it here) or stop being
		// targeted entirely (it was never a primary country).
		$config['was_in_primary'] = ! empty( $config['country'] )
			&& $this->is_country_in_target_audience( $config['country'] );

		$markets        = $this->get_stored_secondary_markets();
		$markets[ $id ] = $config;
		$this->options->update( OptionsInterface::MARKETS, $markets );

		if ( ! empty( $config['country'] ) ) {
			$this->remove_country_from_target_audience( $config['country'] );
			$this->extend_shipping_to_country( $config['country'] );
		}

		// The shipping method is global, so whether Merchant Center needs a sync is
		// decided by the global setting, not by this market's stored snapshot.
		if ( $this->global_shipping_is_syncable() ) {
			$this->schedule_shipping_sync();
		}

		$this->job_repository->get( UpdateAllProducts::class )->schedule();

		/**
		 * Fires after a secondary market is successfully added.
		 *
		 * @param string $id     The market ID.
		 * @param array  $config The market configuration as persisted. The shipping_rate/shipping_time
		 *                       reflect the current global shipping method (see get_markets()).
		 */
		do_action( 'woocommerce_gla_market_added', $id, array_merge( $config, $this->global_shipping_method() ) );
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
			$language_change_pending = array_key_exists( 'language', $config );
			$primary_existing_langs  = $this->get_existing_primary_languages();
			$primary_countries       = $language_change_pending ? $this->target_audience->get_target_countries() : [];

			$existing_target = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$existing_mc     = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

			$this->update_primary_market_fanout( $config );

			if ( $language_change_pending ) {
				$this->schedule_language_cleanup(
					$primary_existing_langs,
					$config['language'],
					$primary_countries
				);
			}

			$merged_target = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$merged_mc     = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

			$existing_countries = $existing_target['countries'] ?? [];
			$merged_countries   = $merged_target['countries'] ?? [];
			$existing_language  = $existing_mc['language'] ?? [];
			$merged_language    = $merged_mc['language'] ?? [];
			$existing_currency  = $existing_mc['currency'] ?? [];
			$merged_currency    = $merged_mc['currency'] ?? [];

			$removed_currencies = array_diff( $existing_currency, $merged_currency );
			if ( ! empty( $removed_currencies ) ) {
				$this->schedule_primary_currency_cleanup( $removed_currencies, $primary_existing_langs );
			}

			$resync_needed = $existing_countries !== $merged_countries
				|| array_diff( $existing_language, $merged_language ) !== []
				|| array_diff( $merged_language, $existing_language ) !== []
				|| array_diff( $existing_currency, $merged_currency ) !== []
				|| array_diff( $merged_currency, $existing_currency ) !== [];

			if ( $resync_needed ) {
				$this->job_repository->get( UpdateAllProducts::class )->schedule();
			}

			return $this->fire_market_updated_action( $id );
		}

		// Flat secondary markets are derived from the shipping tables, so there is no
		// stored config to mutate here: the country's rate/time are edited through the
		// shipping endpoints, and the market's identity (its country) is fixed. Return
		// the current derived market unchanged.
		if ( $this->is_flat_shipping_rate() ) {
			return $this->get_market( $id ) ?? [];
		}

		// Secondary markets don't own a shipping method — it is driven by the global
		// setting (see get_markets()). The Edit Market screen still submits these
		// fields on every save, so drop them rather than letting them mutate the
		// stored snapshot or trip the shipping-sync change detection below.
		unset( $config['shipping_rate'], $config['shipping_time'] );

		$markets  = $this->get_stored_secondary_markets();
		$existing = $markets[ $id ] ?? [];
		$merged   = array_merge( $existing, $config );

		// Markets stored before locale defaulting was introduced may lack the
		// language and currency keys, so partial updates must not fail on them.
		$merged = $this->apply_locale_defaults( $merged );

		$currency_source_touched = array_key_exists( 'currency', $config ) || array_key_exists( 'exchange_rate', $config );

		$this->validate_secondary_market_config( $merged, $currency_source_touched );

		$markets[ $id ] = $merged;
		$this->options->update( OptionsInterface::MARKETS, $markets );

		$old_feed_label = $existing['feed_label'] ?? null;
		$new_feed_label = $merged['feed_label'] ?? null;

		// The derived labels carry the market language and currency, so a
		// feed_label rename, a currency change, and a language removal all
		// leave entries orphaned under labels that are no longer derived.
		$old_labels = $old_feed_label ? $this->get_market_derived_feed_labels( $old_feed_label, $existing ) : [];
		$new_labels = $new_feed_label ? $this->get_market_derived_feed_labels( $new_feed_label, $merged ) : [];

		if ( $old_feed_label && array_diff( $old_labels, $new_labels ) !== [] ) {
			// Clean every key the market's entries may sit under except the
			// labels that remain current, including pre-language-scheme keys.
			$orphaned = array_values(
				array_diff(
					$this->get_market_feed_label_variants(
						$old_feed_label,
						is_array( $existing['language'] ?? null ) ? $existing['language'] : [],
						$this->get_market_currencies( $existing )
					),
					$new_labels
				)
			);

			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule( [ 'feed_labels' => $orphaned ] );
		}

		// shipping_rate/shipping_time are global and were dropped above, so only a
		// country or currency change can affect what this market syncs to Google.
		$shipping_keys = [ 'country', 'currency' ];
		foreach ( $shipping_keys as $key ) {
			if ( ( $existing[ $key ] ?? null ) !== ( $merged[ $key ] ?? null ) ) {
				$this->schedule_shipping_sync();
				break;
			}
		}

		$existing_language = $existing['language'] ?? [];
		$merged_language   = $merged['language'] ?? [];
		$existing_currency = $existing['currency'] ?? [];
		$merged_currency   = $merged['currency'] ?? [];

		// An exchange-rate change alters every feed price for the market, so
		// it needs the same full resync as a currency change.
		$existing_rate = isset( $existing['exchange_rate'] ) ? (float) $existing['exchange_rate'] : null;
		$merged_rate   = isset( $merged['exchange_rate'] ) ? (float) $merged['exchange_rate'] : null;

		$resync_needed = ( $existing['country'] ?? null ) !== ( $merged['country'] ?? null )
			|| ( $existing['feed_label'] ?? null ) !== ( $merged['feed_label'] ?? null )
			|| array_diff( $existing_language, $merged_language ) !== []
			|| array_diff( $merged_language, $existing_language ) !== []
			|| array_diff( $existing_currency, $merged_currency ) !== []
			|| array_diff( $merged_currency, $existing_currency ) !== []
			|| $existing_rate !== $merged_rate;

		if ( $resync_needed ) {
			$this->job_repository->get( UpdateAllProducts::class )->schedule();
		}

		return $this->fire_market_updated_action( $id );
	}

	/**
	 * Schedules CleanupOrphanedLanguageProductsJob when languages were removed
	 * from a market's `language[]` set.
	 *
	 * Language codes are normalised before comparison so locale-form values
	 * (e.g. `en_US`) compare equal to WPML short codes (e.g. `en`).
	 *
	 * Feed labels no longer encode the language, so the keys are the market's
	 * `google_ids` keys as-is (label variants for a secondary market, target
	 * country codes for the primary). The cleanup job narrows the deletion to
	 * the removed languages by each product's own post language.
	 *
	 * @param array         $existing_languages The market's languages before the update.
	 * @param array         $new_languages      The market's languages after the update.
	 * @param array<string> $keys               The `google_ids` keys the market's entries
	 *                                          are tracked under.
	 */
	private function schedule_language_cleanup( array $existing_languages, array $new_languages, array $keys ): void {
		if ( empty( $keys ) ) {
			return;
		}

		$normalised_existing = $this->normalise_language_codes( $existing_languages );
		$normalised_new      = $this->normalise_language_codes( $new_languages );
		$removed             = array_values( array_diff( $normalised_existing, $normalised_new ) );

		if ( empty( $removed ) ) {
			return;
		}

		$keys = array_values( array_unique( array_map( 'strval', $keys ) ) );

		$this->job_repository->get( CleanupOrphanedLanguageProductsJob::class )
			->schedule(
				[
					'keys'              => $keys,
					'removed_languages' => $removed,
				]
			);
	}

	/**
	 * Schedules cleanup of the primary market's entries for currencies removed
	 * from its configured currency list.
	 *
	 * Each removed currency's entries sit under one derived label per primary
	 * language, based on the main feed label. The store currency is never
	 * cleaned up here: its entries live under the bare main feed label, which
	 * stays current regardless of the configured currency list.
	 *
	 * @param array $removed_currencies Currency codes removed from the primary market.
	 * @param array $languages          The primary languages before the update.
	 */
	private function schedule_primary_currency_cleanup( array $removed_currencies, array $languages ): void {
		$main_feed_label = $this->get_main_feed_label();

		if ( '' === $main_feed_label ) {
			return;
		}

		$labels    = [];
		$languages = empty( $languages ) ? [ '' ] : $languages;

		foreach ( $removed_currencies as $currency ) {
			$currency = (string) $currency;
			if ( '' === $currency || get_woocommerce_currency() === $currency ) {
				continue;
			}

			foreach ( $languages as $language ) {
				$labels[] = $this->get_market_feed_label( $main_feed_label, (string) $language, $currency );
			}
		}

		$labels = array_values( array_unique( $labels ) );

		if ( empty( $labels ) ) {
			return;
		}

		$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
			->schedule( [ 'feed_labels' => $labels ] );
	}

	/**
	 * Returns the primary market's language list from the MERCHANT_CENTER option,
	 * falling back to a single-entry list with the site-derived default. Mirrors
	 * the language fallback in get_primary_market() but does not trigger the
	 * shipping-rate or target-audience lookups that get_primary_market() performs.
	 *
	 * @return array
	 */
	private function get_existing_primary_languages(): array {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( is_array( $mc_settings['language'] ?? null ) ) {
			return $mc_settings['language'];
		}

		return [ $this->get_site_primary_language() ];
	}

	/**
	 * Returns the primary market's currency list from the MERCHANT_CENTER option,
	 * falling back to a single-entry list with the site-derived default. Mirrors
	 * get_existing_primary_languages() for the currency field.
	 *
	 * @return array
	 */
	private function get_existing_primary_currencies(): array {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		if ( is_array( $mc_settings['currency'] ?? null ) ) {
			return $mc_settings['currency'];
		}

		return [ $this->get_site_primary_currency() ];
	}

	/**
	 * Normalises an array of language codes to WPML's short-code form so that
	 * locale strings (`en_US`) compare equal to short codes (`en`). Mirrors
	 * the rule in BatchProductHelper::product_matches_market().
	 *
	 * @param array $codes
	 *
	 * @return string[]
	 */
	private function normalise_language_codes( array $codes ): array {
		$normalised = [];
		foreach ( $codes as $code ) {
			$code = (string) $code;
			if ( '' === $code ) {
				continue;
			}
			$normalised[] = false === strpos( $code, '_' ) ? $code : strtolower( substr( $code, 0, 2 ) );
		}

		return array_values( array_unique( $normalised ) );
	}

	/**
	 * Fires the woocommerce_gla_market_updated action and returns the resolved market.
	 *
	 * @param string $id The market ID.
	 *
	 * @return array The updated market, fully resolved (same shape as get_market()).
	 */
	private function fire_market_updated_action( string $id ): array {
		$updated_market = $this->get_market( $id );

		/**
		 * Fires after a market is successfully updated.
		 *
		 * @param string $id             The market ID.
		 * @param array  $updated_market The updated market, fully resolved (same shape as get_market()).
		 */
		do_action( 'woocommerce_gla_market_updated', $id, $updated_market );

		return $updated_market;
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

		// Flat markets are derived from the shipping tables, not persisted. Deleting one
		// removes the country from the store entirely: it leaves the target audience and
		// its shipping rows are dropped (this is effectively editing the target audience).
		if ( $this->is_flat_shipping_rate() ) {
			$secondary = $this->get_secondary_markets_source();
			if ( ! isset( $secondary[ $id ] ) ) {
				return;
			}

			$deleted_config = $secondary[ $id ];
			$country        = $deleted_config['country'] ?? null;
			$feed_label     = $deleted_config['feed_label'] ?? null;

			if ( $country ) {
				$this->remove_country_from_target_audience( $country );
				$this->remove_shipping_rows_for_country( $country );
			}

			if ( $feed_label ) {
				$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
					->schedule(
						[
							'feed_labels' => $this->get_market_feed_label_variants(
								$feed_label,
								is_array( $deleted_config['language'] ?? null ) ? $deleted_config['language'] : [],
								$this->get_market_currencies( $deleted_config )
							),
						]
					);
			}

			if ( $this->global_shipping_is_syncable() ) {
				$this->schedule_shipping_sync();
			}

			$this->job_repository->get( UpdateAllProducts::class )->schedule();

			/** This action is documented in this method's persisted-market branch below. */
			do_action( 'woocommerce_gla_market_deleted', $id, array_merge( $deleted_config, $this->global_shipping_method() ) );

			return;
		}

		$markets = $this->get_stored_secondary_markets();
		if ( ! isset( $markets[ $id ] ) ) {
			return; // Avoid spurious options write and shipping sync for a non-existent market.
		}

		$deleted_config = $markets[ $id ];
		$country        = $deleted_config['country'] ?? null;
		$feed_label     = $deleted_config['feed_label'] ?? null;

		unset( $markets[ $id ] );
		$this->options->update( OptionsInterface::MARKETS, $markets );

		if ( $country ) {
			if ( ! empty( $deleted_config['was_in_primary'] ) ) {
				$this->adopt_primary_rate_for_country( $country );
				$this->adopt_primary_time_for_country( $country );
				$this->restore_country_to_target_audience( $country );
			} else {
				$this->remove_shipping_rows_for_country( $country );
			}
		}

		if ( $feed_label ) {
			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule(
					[
						'feed_labels' => $this->get_market_feed_label_variants(
							$feed_label,
							is_array( $deleted_config['language'] ?? null ) ? $deleted_config['language'] : [],
							$this->get_market_currencies( $deleted_config )
						),
					]
				);
		}

		// The shipping method is global. Deleting a market whose stored snapshot said
		// `manual` while the global rate is flat/automatic must still notify Google,
		// so the decision reads the global setting rather than the deleted snapshot.
		if ( $this->global_shipping_is_syncable() ) {
			$this->schedule_shipping_sync();
		}

		$this->job_repository->get( UpdateAllProducts::class )->schedule();

		/**
		 * Fires after a secondary market is successfully deleted.
		 *
		 * @param string $id             The market ID.
		 * @param array  $deleted_config The market configuration as it existed at the time of deletion.
		 *                               The shipping_rate/shipping_time reflect the current global
		 *                               shipping method (see get_markets()).
		 */
		do_action( 'woocommerce_gla_market_deleted', $id, array_merge( $deleted_config, $this->global_shipping_method() ) );
	}

	/**
	 * Returns every valid `google_ids` tracking key across all configured markets:
	 * one derived feed label per market language-currency pair.
	 *
	 * The primary market keeps its bare label for the store currency so
	 * existing entries keep the identity they already have in Google Merchant
	 * Center, and contributes derived labels for its additional currencies;
	 * each secondary market contributes its language-currency derived labels.
	 *
	 * @return string[]
	 */
	public function get_all_feed_labels(): array {
		$feed_labels = [];

		foreach ( $this->get_market_labels_with_languages() as $market ) {
			if ( '' === $market['feed_label'] ) {
				continue;
			}

			$feed_labels[] = $market['feed_label'];
		}

		return array_values( array_unique( $feed_labels ) );
	}

	/**
	 * Returns the feed label to use for a secondary market's product entries.
	 *
	 * A Merchant Center feed is one language-currency pair (PRD), so the
	 * stored feed label gets the uppercase two-letter language code and the
	 * uppercase ISO 4217 currency code appended, e.g. "BE-FR-EUR". A market
	 * configured with several languages produces one label per language.
	 *
	 * The primary market never uses this derivation; it keeps its bare label
	 * (see get_main_feed_label()) so existing entries keep their identity.
	 *
	 * An empty base label returns an empty string, so callers do not need
	 * their own empty-label branch.
	 *
	 * @param string $base_feed_label The market's stored feed label.
	 * @param string $language        Language code in short ("fr") or locale
	 *                                ("fr_FR") form. Empty falls back to the
	 *                                site primary language.
	 * @param string $currency        ISO 4217 currency code. Empty falls back to
	 *                                the store currency, matching the currency
	 *                                the entries' prices are submitted in.
	 *
	 * @return string
	 */
	public function get_market_feed_label( string $base_feed_label, string $language, string $currency ): string {
		if ( '' === $base_feed_label ) {
			return '';
		}

		$normalised = $this->normalise_language_codes( [ $language ] );
		$language   = $normalised[0] ?? '';

		if ( '' === $language ) {
			$language = $this->get_normalised_site_language();
		}

		if ( '' === $currency ) {
			$currency = get_woocommerce_currency();
		}

		// A non-multilingual store has a single language, so its store-currency feed collapses
		// to one feed per market. That feed uses the bare base label (like the primary market),
		// matching the design that flat/non-multilingual markets carry no language/currency in
		// their feed label. Non-store currencies (e.g. an exchange-rate market) keep the suffix
		// so their distinct feed stays uniquely labelled.
		if ( ! $this->has_multilingual_support() && get_woocommerce_currency() === $currency ) {
			return strtoupper( $base_feed_label );
		}

		return strtoupper( $base_feed_label . '-' . substr( $language, 0, 2 ) . '-' . $currency );
	}

	/**
	 * Returns a market's configured currency codes, uppercased and deduplicated,
	 * or a single-entry list with the store currency when none is configured.
	 * Uppercasing matches WCML's uppercase currency_options keys. Every configured
	 * currency produces its own feeds; see get_participating_currencies() for the
	 * subset currently allowed to sync, and get_market_currencies_for_language()
	 * for the subset a given language emits.
	 *
	 * @param array $market The market config.
	 *
	 * @return string[]
	 */
	private function get_market_currencies( array $market ): array {
		$configured = is_array( $market['currency'] ?? null )
			? $market['currency']
			: [ $market['currency'] ?? '' ];

		$currencies = [];
		foreach ( $configured as $currency ) {
			// Uppercase to match WCML's uppercase currency_options keys and dedupe case-insensitively.
			$currency = strtoupper( (string) $currency );
			if ( '' !== $currency ) {
				$currencies[] = $currency;
			}
		}

		$currencies = array_values( array_unique( $currencies ) );

		return empty( $currencies ) ? [ get_woocommerce_currency() ] : $currencies;
	}

	/**
	 * Returns the market currencies that currently take part in syncing: the
	 * store currency always takes part, and any other currency only while
	 * price conversion is available (WPML active with WCML multi-currency on)
	 * or while the market carries a configured exchange rate, which is its
	 * own conversion source.
	 *
	 * @param array $market The market config.
	 *
	 * @return string[]
	 */
	public function get_participating_currencies( array $market ): array {
		$store_currency = get_woocommerce_currency();
		$can_convert    = $this->wpml->can_convert_currency();
		$has_rate       = isset( $market['exchange_rate'] ) && is_numeric( $market['exchange_rate'] ) && (float) $market['exchange_rate'] > 0;

		return array_values(
			array_filter(
				$this->get_market_currencies( $market ),
				function ( string $currency ) use ( $store_currency, $can_convert, $has_rate ): bool {
					return $currency === $store_currency || $can_convert || $has_rate;
				}
			)
		);
	}

	/**
	 * The currencies a market emits a feed for under a language: its participating currencies
	 * (see get_participating_currencies()) narrowed to those enabled for the language via WCML.
	 * Both gates apply, so a currency must be convertible AND enabled for the language. An empty
	 * language is not narrowed. May be empty if no currency survives, in which case the market
	 * emits no feed for that language.
	 *
	 * @param array  $market   The market config.
	 * @param string $language Language code the feed syncs under, or '' when the market has no languages.
	 *
	 * @return string[]
	 */
	public function get_market_currencies_for_language( array $market, string $language ): array {
		$currencies = $this->get_participating_currencies( $market );

		if ( empty( $currencies ) ) {
			return [];
		}

		$normalised = $this->normalise_language_codes( [ $language ] );
		$language   = $normalised[0] ?? '';

		if ( '' === $language ) {
			return $currencies;
		}

		return $this->wpml->get_currencies_enabled_for_language( $currencies, $language );
	}

	/**
	 * Returns the derived feed label of every market that accepts the given language.
	 *
	 * A market with an empty language list accepts every language, mirroring the
	 * matching rule used when generating sync requests. Used to decide whether a
	 * product has been synced everywhere its language allows.
	 *
	 * @param string $language ISO 639-1 code or locale string.
	 *
	 * @return string[]
	 */
	public function get_feed_labels_for_language( string $language ): array {
		$normalised = $this->normalise_language_codes( [ $language ] );
		$language   = $normalised[0] ?? '';

		if ( '' === $language ) {
			$language = $this->get_normalised_site_language();
		}

		$feed_labels = [];
		foreach ( $this->get_market_labels_with_languages() as $market ) {
			if ( '' === $market['feed_label'] ) {
				continue;
			}

			$market_languages = $this->normalise_language_codes( $market['languages'] );
			if ( ! empty( $market_languages ) && ! in_array( $language, $market_languages, true ) ) {
				continue;
			}

			$feed_labels[] = $market['feed_label'];
		}

		return array_values( array_unique( $feed_labels ) );
	}

	/**
	 * Returns each market's derived feed labels together with the languages they match.
	 *
	 * The primary market contributes its bare main feed label with the primary
	 * language list (the store currency's feed, kept bare so existing entries
	 * keep their Merchant Center identity), plus one derived label per primary
	 * language per additional participating primary currency. Each
	 * participating secondary market contributes one language-currency derived
	 * label per configured language per participating currency, each paired
	 * with that single language; a market with no configured languages
	 * contributes one site-language label per participating currency, paired
	 * with an empty list (matching every language). Excluded markets and
	 * currencies (see get_participating_currencies()) contribute nothing,
	 * which is what lets the stale-entry cleanup remove their Merchant Center
	 * entries.
	 *
	 * @return array<int, array{feed_label: string, languages: array}>
	 */
	private function get_market_labels_with_languages(): array {
		$main_feed_label   = $this->get_main_feed_label();
		$primary_languages = $this->get_existing_primary_languages();

		$markets = [
			[
				'feed_label' => $main_feed_label,
				'languages'  => $primary_languages,
			],
		];

		$primary_extra_currencies = array_diff(
			$this->get_participating_currencies( [ 'currency' => $this->get_existing_primary_currencies() ] ),
			[ get_woocommerce_currency() ]
		);

		if ( '' !== $main_feed_label ) {
			foreach ( $primary_extra_currencies as $currency ) {
				foreach ( $primary_languages as $language ) {
					$markets[] = [
						'feed_label' => $this->get_market_feed_label( $main_feed_label, (string) $language, $currency ),
						'languages'  => [ (string) $language ],
					];
				}
			}
		}

		foreach ( $this->get_participating_secondary_markets() as $market ) {
			$market          = $this->apply_site_locale_when_not_multilingual( $market );
			$base_feed_label = (string) ( $market['feed_label'] ?? '' );
			$languages       = is_array( $market['language'] ?? null ) ? $market['language'] : [];

			if ( empty( $languages ) ) {
				// A market with no languages syncs under the site-language label: one label
				// per enabled currency.
				foreach ( $this->get_market_currencies_for_language( $market, '' ) as $currency ) {
					$markets[] = [
						'feed_label' => $this->get_market_feed_label( $base_feed_label, '', $currency ),
						'languages'  => [],
					];
				}
				continue;
			}

			foreach ( $languages as $language ) {
				foreach ( $this->get_market_currencies_for_language( $market, (string) $language ) as $currency ) {
					$markets[] = [
						'feed_label' => $this->get_market_feed_label( $base_feed_label, (string) $language, $currency ),
						'languages'  => [ (string) $language ],
					];
				}
			}
		}

		return $markets;
	}

	/**
	 * Returns every `google_ids` key a market's entries can be stored under: the base feed label
	 * and one language-currency label per configured language and currency. The base label is
	 * always included so entries synced under the earliest scheme, which used the stored label
	 * verbatim, are still found and cleaned up. The intermediate currency-only scheme is not
	 * covered: it never shipped, so its keys cannot exist outside intermediate builds.
	 *
	 * @param string   $feed_label The market's stored feed label.
	 * @param array    $languages  The market's configured language codes. An
	 *                             empty list contributes the site-language label.
	 * @param string[] $currencies The market's configured currency codes. An
	 *                             empty list contributes the store-currency labels.
	 *
	 * @return string[]
	 */
	private function get_market_feed_label_variants( string $feed_label, array $languages, array $currencies ): array {
		$currencies = empty( $currencies ) ? [ get_woocommerce_currency() ] : $currencies;
		$languages  = empty( $languages ) ? [ '' ] : $languages;

		$variants = [ $feed_label ];

		foreach ( $currencies as $currency ) {
			$currency = '' === (string) $currency ? get_woocommerce_currency() : (string) $currency;

			foreach ( $languages as $language ) {
				$variants[] = $this->get_market_feed_label( $feed_label, (string) $language, $currency );
			}
		}

		return array_values( array_unique( $variants ) );
	}

	/**
	 * Returns the current derived feed label set for a market config: one
	 * language-currency label per configured language per configured currency
	 * (the site-language labels when no languages are configured).
	 *
	 * @param string $feed_label The market's stored feed label.
	 * @param array  $market     The market config the languages and currencies come from.
	 *
	 * @return string[]
	 */
	private function get_market_derived_feed_labels( string $feed_label, array $market ): array {
		$languages = is_array( $market['language'] ?? null ) && ! empty( $market['language'] )
			? $market['language']
			: [ '' ];

		$labels = [];

		// Cleanup targeting uses the unfiltered currency list. Narrowing here by conversion
		// availability would turn every non-store-currency feed into an orphan the moment
		// conversion went away; a superset is the safe direction for orphan computation.
		foreach ( $this->get_market_currencies( $market ) as $currency ) {
			foreach ( $languages as $language ) {
				$labels[] = $this->get_market_feed_label( $feed_label, (string) $language, $currency );
			}
		}

		return array_values( array_unique( $labels ) );
	}

	/**
	 * Returns the site default language as a normalised short code.
	 *
	 * @return string
	 */
	private function get_normalised_site_language(): string {
		$normalised = $this->normalise_language_codes( [ $this->get_site_primary_language() ] );

		return $normalised[0] ?? '';
	}

	/**
	 * Returns the feed label for the primary market.
	 *
	 * @return string Defaults to the store's main target country code when no
	 *                custom feedLabel has been configured.
	 */
	public function get_main_feed_label(): string {
		return $this->target_audience->get_main_target_country();
	}

	/**
	 * Returns every country code across all participating markets without duplicates.
	 *
	 * The primary market contributes its target_audience countries; each
	 * participating secondary market contributes its single `country` value.
	 * Excluded markets (see get_participating_markets()) contribute nothing
	 * so products stop advertising shipping to countries that are not being
	 * synced. No DB queries.
	 *
	 * @return string[]
	 */
	public function get_all_countries(): array {
		$secondary = $this->get_participating_secondary_markets();
		$countries = $this->target_audience->get_target_countries();

		if ( ! empty( $secondary ) ) {
			foreach ( $secondary as $market ) {
				$countries[] = $market['country'];
			}
		}

		return array_unique( $countries );
	}

	/**
	 * Returns every country that should receive a Merchant Center shipping
	 * service: the primary market's target countries plus each participating,
	 * non-manual secondary market's country.
	 *
	 * Manual markets are excluded because their shipping is managed outside
	 * the plugin, mirroring the per-market currency map in the Google
	 * Settings service. Markets excluded from syncing (see
	 * get_participating_markets()) get no shipping service either.
	 *
	 * @return string[]
	 */
	public function get_shipping_sync_countries(): array {
		$countries = $this->target_audience->get_target_countries();

		foreach ( $this->get_participating_secondary_markets() as $market ) {
			if ( 'manual' === ( $market['shipping_rate'] ?? null ) ) {
				continue;
			}

			if ( ! empty( $market['country'] ) ) {
				$countries[] = $market['country'];
			}
		}

		return array_values( array_unique( $countries ) );
	}

	/**
	 * Whether any configured market needs its shipping settings synced to Merchant Center.
	 *
	 * A market is syncable when its `shipping_rate` is `flat` or `automatic` and its
	 * `shipping_time` is `flat`. Anything else (`manual`, or a missing or unrecognised
	 * value) is not syncable and must not schedule a sync, because the DB shipping
	 * adapter would then be asked to push rates the merchant never entered.
	 *
	 * Only participating markets count: a syncable secondary market is enough to
	 * require a sync even when the primary itself is `manual`, while markets
	 * excluded from syncing (see get_participating_markets()) never require one.
	 * Every market reflects the global shipping method (see get_markets()).
	 *
	 * @return bool
	 */
	public function has_syncable_markets(): bool {
		foreach ( $this->get_participating_markets() as $market ) {
			if ( $this->is_syncable_shipping_method( $market['shipping_rate'] ?? null, $market['shipping_time'] ?? null ) ) {
				return true;
			}
		}

		return false;
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
	 * Returns the store's active languages from the multilingual integration,
	 * falling back to a single entry for the site's default language when the
	 * integration has none configured yet.
	 *
	 * Without this fallback the Markets edit UI's language dropdown has no
	 * options to choose from, even though the site default is already what
	 * newly created markets use (see get_site_primary_language()).
	 *
	 * @return array<int, array{code: string, label: string}>
	 */
	public function get_languages(): array {
		$languages = $this->wpml->get_languages();

		if ( ! empty( $languages ) ) {
			return $languages;
		}

		return [
			[
				'code'  => $this->get_default_site_language_code(),
				'label' => $this->get_default_site_language_label(),
			],
		];
	}

	/**
	 * Returns the store's active currencies from the multilingual integration,
	 * falling back to a single entry for the store's default currency when the
	 * integration has none configured yet.
	 *
	 * WPML ties each currency's `languages` to its own (possibly empty) language
	 * list, so a currency can come back with no languages even when currencies
	 * themselves are configured — that leaves it unselectable once the site's
	 * default language (see get_languages()) is chosen instead. When WPML has no
	 * languages, every currency is re-pointed at our fallback-aware language list
	 * so it stays selectable.
	 *
	 * @return array<int, array{code: string, symbol: string, languages: string[]}>
	 */
	public function get_currencies(): array {
		$currencies = $this->wpml->get_currencies();

		if ( empty( $this->wpml->get_languages() ) ) {
			$fallback_language_codes = [ $this->get_default_site_language_code() ];

			foreach ( $currencies as &$currency ) {
				$currency['languages'] = $fallback_language_codes;
			}
			unset( $currency );
		}

		if ( ! empty( $currencies ) ) {
			return $currencies;
		}

		$code = get_woocommerce_currency();

		if ( '' === $code || ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return [];
		}

		return [
			[
				'code'      => $code,
				'symbol'    => html_entity_decode( get_woocommerce_currency_symbol( $code ), ENT_QUOTES, 'UTF-8' ),
				'languages' => [ $this->get_default_site_language_code() ],
			],
		];
	}

	/**
	 * Schedules the shipping-settings sync job so MC shipping services are
	 * regenerated for every non-manual market.
	 */
	private function schedule_shipping_sync(): void {
		$this->job_repository->get( UpdateShippingSettings::class )->schedule();
	}

	/**
	 * Returns the stored secondary markets from the Markets option.
	 *
	 * Returns the stored values verbatim: write paths (add, update, delete)
	 * merge against this data, so stored locale values must survive partial
	 * updates even while no multilingual integration is active. Consumption
	 * paths mask locale values via apply_site_locale_when_not_multilingual().
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
	 * Overrides a market config's locale values with the site defaults when no
	 * multilingual integration is active, and returns it unchanged otherwise.
	 *
	 * Without an integration the store cannot produce translated content or
	 * converted prices, so locale values saved while an integration was still
	 * active (for example a EUR currency on a USD store) must not drive feed
	 * labels, submitted prices, or shipping currencies. The stored option is
	 * left untouched so the values come back when the integration returns.
	 *
	 * @param array $market The market config.
	 *
	 * @return array The config with `language` and `currency` replaced by the
	 *               site primary language and the store currency when the site
	 *               is not multilingual.
	 */
	private function apply_site_locale_when_not_multilingual( array $market ): array {
		if ( $this->has_multilingual_support() ) {
			return $market;
		}

		$market['language'] = [ $this->get_site_primary_language() ];

		// A configured exchange rate is its own conversion source, so the
		// market's stored currency stays meaningful without a multilingual
		// integration and must survive the masking.
		if ( empty( $market['exchange_rate'] ) ) {
			$market['currency'] = [ $this->get_site_primary_currency() ];
		}

		return $market;
	}

	/**
	 * Fans out a primary market update to the underlying settings stores.
	 *
	 * @param array $config Partial config — only supplied keys are written.
	 *
	 * @throws InvalidValue When `language` or `currency` is present but not an array.
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

		foreach ( [ 'language', 'currency' ] as $key ) {
			if ( ! array_key_exists( $key, $config ) ) {
				continue;
			}
			if ( ! is_array( $config[ $key ] ) ) {
				throw InvalidValue::not_array( $key );
			}
			$mc_settings[ $key ] = array_values( array_unique( $config[ $key ] ) );
			$mc_updated          = true;
		}

		if ( $mc_updated ) {
			$this->options->update( OptionsInterface::MERCHANT_CENTER, $mc_settings );
		}

		if ( array_key_exists( 'countries', $config ) ) {
			$countries = $config['countries'];

			if ( $this->is_flat_shipping_rate() ) {
				// The submitted primary market countries are already filtered to exclude
				// flat-derived secondary markets (see get_primary_market_countries()). Merge
				// those back in so saving the primary market doesn't drop them from the
				// target audience — which would delete their derived secondary markets too,
				// since get_derived_flat_secondary_markets() only considers countries still
				// present in the target audience.
				$secondary_countries = array_column( $this->get_derived_flat_secondary_markets(), 'country' );
				$countries           = array_values( array_unique( array_merge( $countries, $secondary_countries ) ) );
			}

			$target_audience              = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$target_audience['countries'] = $countries;
			$this->options->update( OptionsInterface::TARGET_AUDIENCE, $target_audience );
		}
	}

	/**
	 * Returns the free-shipping threshold for the primary market's country.
	 *
	 * Uses get_main_target_country() as a single-country fallback for the aggregated
	 * free_shipping field; multi-country aggregation is out of scope.
	 *
	 * @return float|null The threshold amount, or null when unset.
	 */
	private function get_primary_free_shipping_threshold(): ?float {
		if ( ! $this->is_flat_shipping_rate() ) {
			return null;
		}

		$country = $this->target_audience->get_main_target_country();
		$rates   = $this->get_cached_shipping_rates();

		if ( isset( $rates[ $country ]['free_shipping_threshold'] ) ) {
			return (float) $rates[ $country ]['free_shipping_threshold'];
		}

		return null;
	}

	/**
	 * Whether the global shipping rate mode is 'flat'.
	 *
	 * DB-stored rates only flow to MC in flat mode; automatic mode is driven by
	 * WC shipping zones, and manual mode is handled outside the plugin.
	 *
	 * @return bool
	 */
	private function is_flat_shipping_rate(): bool {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );

		return is_array( $mc_settings ) && 'flat' === ( $mc_settings['shipping_rate'] ?? null );
	}

	/**
	 * Returns the store-wide shipping method from the MERCHANT_CENTER option.
	 *
	 * This is the single source of truth for every market's shipping_rate and
	 * shipping_time; both values are null when the setting is unset.
	 *
	 * @return array{shipping_rate: string|null, shipping_time: string|null}
	 */
	private function global_shipping_method(): array {
		$mc_settings = $this->options->get( OptionsInterface::MERCHANT_CENTER, [] );
		if ( ! is_array( $mc_settings ) ) {
			$mc_settings = [];
		}

		return [
			'shipping_rate' => $mc_settings['shipping_rate'] ?? null,
			'shipping_time' => $mc_settings['shipping_time'] ?? null,
		];
	}

	/**
	 * Whether the global shipping method can be synced to Merchant Center.
	 *
	 * Used by add_market()/delete_market() to decide whether the change needs to be
	 * pushed to Google. Mirrors the per-market predicate in has_syncable_markets().
	 *
	 * @return bool
	 */
	private function global_shipping_is_syncable(): bool {
		$global = $this->global_shipping_method();

		return $this->is_syncable_shipping_method( $global['shipping_rate'], $global['shipping_time'] );
	}

	/**
	 * Whether a shipping method (rate + time) is syncable to Merchant Center.
	 *
	 * @param string|null $rate The shipping_rate value.
	 * @param string|null $time The shipping_time value.
	 *
	 * @return bool True when the rate is `flat` or `automatic` and the time is `flat`.
	 */
	private function is_syncable_shipping_method( $rate, $time ): bool {
		return in_array( $rate, [ 'flat', 'automatic' ], true ) && 'flat' === $time;
	}

	/**
	 * Returns shipping rates, fetched lazily and cached on the service instance.
	 *
	 * The container shares MarketService across a request, so this cache lives for
	 * the request and is discarded when the instance is destroyed.
	 *
	 * @return array
	 */
	private function get_cached_shipping_rates(): array {
		if ( null === $this->cached_shipping_rates ) {
			$this->cached_shipping_rates = $this->shipping_rate_query->get_all_shipping_rates();
		}

		return $this->cached_shipping_rates;
	}

	/**
	 * Fills in a secondary market config's missing locale values.
	 *
	 * A missing `language` defaults to the site primary language and a missing
	 * `currency` to the site primary currency (the WooCommerce store currency
	 * when no multilingual integration is active). Present values, including
	 * empty arrays, are left untouched.
	 *
	 * @param array $config The market config.
	 *
	 * @return array The config with locale defaults applied.
	 */
	private function apply_locale_defaults( array $config ): array {
		if ( ! isset( $config['language'] ) ) {
			$config['language'] = [ $this->get_site_primary_language() ];
		}

		if ( ! isset( $config['currency'] ) ) {
			$config['currency'] = [ $this->get_site_primary_currency() ];
		}

		return $config;
	}

	/**
	 * Validates that a secondary market config contains the required keys.
	 *
	 * @param array $config                   The config to validate (full or merged).
	 * @param bool  $validate_currency_source Whether to require the market's currency to be
	 *                                        producible or covered by an exchange rate. True
	 *                                        for creates and for updates that touch the
	 *                                        currency or exchange rate; false for partial
	 *                                        updates that leave them untouched, so unrelated
	 *                                        edits of markets stored before this validation
	 *                                        existed keep working.
	 *
	 * @throws InvalidValue When a required key is missing or invalid.
	 */
	private function validate_secondary_market_config( array $config, bool $validate_currency_source = true ): void {
		foreach ( [ 'country', 'feed_label' ] as $key ) {
			if ( empty( $config[ $key ] ) || ! is_string( $config[ $key ] ) ) {
				throw InvalidValue::is_empty( $key );
			}
		}

		if ( ! preg_match( self::FEED_LABEL_PATTERN, $config['feed_label'] ) ) {
			throw InvalidValue::does_not_match_pattern( 'feed_label', self::FEED_LABEL_PATTERN, $config['feed_label'] );
		}

		foreach ( [ 'language', 'currency' ] as $key ) {
			if ( ! isset( $config[ $key ] ) ) {
				throw InvalidValue::is_empty( $key );
			}
			if ( ! is_array( $config[ $key ] ) ) {
				throw InvalidValue::not_array( $key );
			}
		}

		if ( isset( $config['exchange_rate'] ) && ( ! is_numeric( $config['exchange_rate'] ) || (float) $config['exchange_rate'] <= 0 ) ) {
			throw new InvalidValue( 'The exchange_rate must be a number greater than zero.' );
		}

		// A currency the site cannot produce is only valid with a fixed
		// exchange rate to produce it at sync time.
		if ( $validate_currency_source && empty( $config['exchange_rate'] ) ) {
			$producible = array_column( $this->get_currencies(), 'code' );

			if ( empty( $producible ) ) {
				$producible = [ get_woocommerce_currency() ];
			}

			foreach ( $config['currency'] as $currency_code ) {
				if ( ! in_array( (string) $currency_code, $producible, true ) ) {
					throw new InvalidValue(
						sprintf( 'The currency "%s" cannot be produced by this site. Configure an exchange rate for the market or use a producible currency.', $currency_code )
					);
				}
			}
		}

		// Every currency must be enabled for at least one selected language, else no feed could
		// ever be generated for it. Normalise through get_market_currencies() so the check runs on
		// the same uppercased codes feed generation uses, otherwise a lowercase currency slips past.
		$languages  = $this->normalise_language_codes( array_map( 'strval', $config['language'] ) );
		$currencies = $this->get_market_currencies( $config );

		if ( ! empty( $currencies ) && ! empty( $languages ) ) {
			$enabled = [];
			foreach ( $languages as $language ) {
				$enabled = array_merge( $enabled, $this->wpml->get_currencies_enabled_for_language( $currencies, (string) $language ) );
			}
			$enabled = array_values( array_unique( $enabled ) );

			if ( ! empty( array_diff( $currencies, $enabled ) ) ) {
				throw InvalidValue::not_in_allowed_list( 'currency', $enabled );
			}
		}
	}

	/**
	 * Returns the site's default language as an ISO 639-1 code, independent of
	 * any multilingual integration state.
	 *
	 * Used to build the fallback language entry in get_languages(); must not
	 * call get_site_primary_language(), which resolves through get_languages()
	 * and would recurse.
	 *
	 * @return string
	 */
	private function get_default_site_language_code(): string {
		return substr( get_locale(), 0, 2 );
	}

	/**
	 * Returns a human-readable label for the site's default language.
	 *
	 * @return string
	 */
	private function get_default_site_language_label(): string {
		$locale = get_locale();

		if ( class_exists( Locale::class ) ) {
			return Locale::getDisplayLanguage( $locale, $locale );
		}

		// en_US isn't provided by the translations API.
		if ( 'en_US' === $locale ) {
			return 'English';
		}

		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		return wp_get_available_translations()[ $locale ]['native_name'] ?? $locale;
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
	 * Extends existing shipping settings to a newly targeted country.
	 *
	 * Copies the primary market's shipping rate and time rows for the country
	 * when no row exists for it yet. Existing rows are never overwritten, so a
	 * country that is already covered keeps its configured values.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code of the added market.
	 */
	private function extend_shipping_to_country( string $country ): void {
		$has_rate_row = false;
		$rate_rows    = $this->shipping_rate_query->get_results() ?? [];
		foreach ( $rate_rows as $row ) {
			if ( $row['country'] === $country ) {
				$has_rate_row = true;
				break;
			}
		}

		if ( ! $has_rate_row ) {
			$this->adopt_primary_rate_for_country( $country );
		}

		$has_time_row = false;
		$time_rows    = $this->shipping_time_query->get_results() ?? [];
		foreach ( $time_rows as $row ) {
			if ( $row['country'] === $country ) {
				$has_time_row = true;
				break;
			}
		}

		if ( ! $has_time_row ) {
			$this->adopt_primary_time_for_country( $country );
		}

		// Rows may have been inserted, so reads later in the request must not
		// serve the pre-insert cache.
		$this->cached_shipping_rates = null;
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
		$rows            = $this->shipping_rate_query->get_results() ?? [];

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
		$rows            = $this->shipping_time_query->get_results() ?? [];

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

	/**
	 * Whether a country is currently in the primary feed's TargetAudience countries list.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool
	 */
	private function is_country_in_target_audience( string $country ): bool {
		$target_audience = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );

		return ! empty( $target_audience['countries'] )
			&& is_array( $target_audience['countries'] )
			&& in_array( $country, $target_audience['countries'], true );
	}

	/**
	 * Removes a country's shipping rate and time rows.
	 *
	 * Used when a deleted market's country is not returning to the primary
	 * market: with no rows and no target audience entry, the country is
	 * omitted from the next shipping settings payload, and Google deletes its
	 * shipping service because shippingsettings.update replaces the full
	 * resource ("any fields that are not provided are deleted").
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 */
	private function remove_shipping_rows_for_country( string $country ): void {
		$this->shipping_rate_query->delete( 'country', $country );
		$this->shipping_time_query->delete( 'country', $country );
		$this->cached_shipping_rates = null;
	}
}
