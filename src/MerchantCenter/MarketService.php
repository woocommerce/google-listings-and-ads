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
	 * uppercase letters, digits and dashes only.
	 */
	private const FEED_LABEL_PATTERN = '/^[A-Z0-9-]{1,20}$/';

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
	 * No WordPress hooks are needed for this pure data service.
	 */
	public function register(): void {}

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
		$stored    = $this->options->get( OptionsInterface::MARKETS );
		$secondary = is_array( $stored ) ? $stored : [];
		unset( $secondary['primary'] );

		$all_rates       = $this->get_cached_shipping_rates();
		$all_countries   = $this->wc->get_countries();
		$is_flat_mode    = $this->is_flat_shipping_rate();
		$global_shipping = $this->global_shipping_method();

		foreach ( $secondary as &$market ) {
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
			'countries'     => $this->target_audience->get_target_countries(),
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
			$primary_existing_langs  = $language_change_pending ? $this->get_existing_primary_languages() : [];
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

		// Secondary markets don't own a shipping method — it is driven by the global
		// setting (see get_markets()). The Edit Market screen still submits these
		// fields on every save, so drop them rather than letting them mutate the
		// stored snapshot or trip the shipping-sync change detection below.
		unset( $config['shipping_rate'], $config['shipping_time'] );

		$markets  = $this->get_stored_secondary_markets();
		$existing = $markets[ $id ] ?? [];
		$merged   = array_merge( $existing, $config );

		$this->validate_secondary_market_config( $merged );

		$markets[ $id ] = $merged;
		$this->options->update( OptionsInterface::MARKETS, $markets );

		$old_feed_label = $existing['feed_label'] ?? null;
		$new_feed_label = $merged['feed_label'] ?? null;
		if ( $old_feed_label && $new_feed_label !== $old_feed_label ) {
			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule( [ 'feed_label' => $old_feed_label ] );
		}

		if ( $old_feed_label ) {
			$this->schedule_language_cleanup(
				$existing['language'] ?? [],
				$merged['language'] ?? [],
				[ $old_feed_label ]
			);
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

		$resync_needed = ( $existing['country'] ?? null ) !== ( $merged['country'] ?? null )
			|| ( $existing['feed_label'] ?? null ) !== ( $merged['feed_label'] ?? null )
			|| array_diff( $existing_language, $merged_language ) !== []
			|| array_diff( $merged_language, $existing_language ) !== []
			|| array_diff( $existing_currency, $merged_currency ) !== []
			|| array_diff( $merged_currency, $existing_currency ) !== [];

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
	 * @param array         $existing_languages The market's languages before the update.
	 * @param array         $new_languages      The market's languages after the update.
	 * @param array<string> $keys               `google_ids` keys belonging to the market
	 *                                          (feed_label for secondary, target countries for primary).
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

		$this->job_repository->get( CleanupOrphanedLanguageProductsJob::class )
			->schedule(
				[
					'keys'              => $keys,
					'removed_languages' => $removed,
				]
			);
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
			$this->adopt_primary_rate_for_country( $country );
			$this->adopt_primary_time_for_country( $country );
			$this->restore_country_to_target_audience( $country );
		}

		if ( $feed_label ) {
			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule( [ 'feed_label' => $feed_label ] );
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
	 * Returns the feed label values for every configured market.
	 *
	 * @return array
	 */
	public function get_all_feed_labels(): array {
		$secondary   = $this->get_stored_secondary_markets();
		$feed_labels = array_column( array_values( $secondary ), 'feed_label' );
		array_unshift( $feed_labels, $this->get_main_feed_label() );

		return $feed_labels;
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
	 * Returns every country code across all markets without duplicates.
	 *
	 * The primary market contributes its target_audience countries; each
	 * secondary market contributes its single `country` value. No DB queries.
	 *
	 * @return string[]
	 */
	public function get_all_countries(): array {
		$secondary = $this->get_stored_secondary_markets();
		$countries = $this->target_audience->get_target_countries();

		if ( ! empty( $secondary ) ) {
			foreach ( $secondary as $market ) {
				$countries[] = $market['country'];
			}
		}

		return array_unique( $countries );
	}

	/**
	 * Whether any configured market needs its shipping settings synced to Merchant Center.
	 *
	 * A market is syncable when its `shipping_rate` is `flat` or `automatic` and its
	 * `shipping_time` is `flat`. Anything else — `manual`, or a missing/unrecognised
	 * value — is not syncable and must not schedule a sync, because the DB shipping
	 * adapter would then be asked to push rates the merchant never entered.
	 *
	 * Every market reflects the global shipping method (see get_markets()), so in
	 * practice this answers "is the global shipping method syncable?".
	 *
	 * @return bool
	 */
	public function has_syncable_markets(): bool {
		foreach ( $this->get_markets() as $market ) {
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
	 * Schedules the shipping-settings sync job so MC shipping services are
	 * regenerated for every non-manual market.
	 */
	private function schedule_shipping_sync(): void {
		$this->job_repository->get( UpdateShippingSettings::class )->schedule();
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
			$target_audience              = $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
			$target_audience['countries'] = $config['countries'];
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
