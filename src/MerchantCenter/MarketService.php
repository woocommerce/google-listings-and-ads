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
	 * No WordPress hooks are needed for this pure data service.
	 */
	public function register(): void {}

	/**
	 * Returns all markets, keyed by ID with the synthesised primary always first.
	 *
	 * @return array[] Keyed by market ID ('primary', then secondary IDs).
	 */
	public function get_markets(): array {
		$secondary = $this->get_stored_secondary_markets();

		$all_rates     = $this->get_cached_shipping_rates();
		$all_countries = $this->wc->get_countries();
		$is_flat_mode  = $this->is_flat_shipping_rate();

		foreach ( $secondary as &$market ) {
			$market  = $this->apply_site_locale_when_not_multilingual( $market );
			$country = $market['country'] ?? null;

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

		if ( 'manual' !== ( $config['shipping_rate'] ?? null ) ) {
			$this->schedule_shipping_sync();
		}

		$this->job_repository->get( UpdateAllProducts::class )->schedule();

		/**
		 * Fires after a secondary market is successfully added.
		 *
		 * @param string $id     The market ID.
		 * @param array  $config The market configuration as persisted, including shipping_rate and shipping_time defaults.
		 */
		do_action( 'woocommerce_gla_market_added', $id, $config );
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

		$markets  = $this->get_stored_secondary_markets();
		$existing = $markets[ $id ] ?? [];
		$merged   = array_merge( $existing, $config );

		// Markets stored before locale defaulting was introduced may lack the
		// language and currency keys, so partial updates must not fail on them.
		$merged = $this->apply_locale_defaults( $merged );

		$this->validate_secondary_market_config( $merged );

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
						$this->get_market_currency( $existing )
					),
					$new_labels
				)
			);

			$this->job_repository->get( CleanupOrphanedMarketProductsJob::class )
				->schedule( [ 'feed_labels' => $orphaned ] );
		}

		$shipping_keys = [ 'country', 'currency', 'shipping_rate', 'shipping_time' ];
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
		$shipping_rate  = $deleted_config['shipping_rate'] ?? null;

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
							$this->get_market_currency( $deleted_config )
						),
					]
				);
		}

		if ( 'manual' !== $shipping_rate ) {
			$this->schedule_shipping_sync();
		}

		$this->job_repository->get( UpdateAllProducts::class )->schedule();

		/**
		 * Fires after a secondary market is successfully deleted.
		 *
		 * @param string $id             The market ID.
		 * @param array  $deleted_config The market configuration as it existed at the time of deletion.
		 */
		do_action( 'woocommerce_gla_market_deleted', $id, $deleted_config );
	}

	/**
	 * Returns every valid `google_ids` tracking key across all configured markets:
	 * one derived feed label per market.
	 *
	 * The primary market keeps its bare label so existing entries keep the
	 * identity they already have in Google Merchant Center; each secondary
	 * market contributes its currency-derived label.
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

		return strtoupper( $base_feed_label . '-' . substr( $language, 0, 2 ) . '-' . $currency );
	}

	/**
	 * Returns a market's effective currency code: the first configured entry
	 * of its `currency[]` list, or the store currency when none is set. This
	 * is the currency the market's prices are submitted in.
	 *
	 * @param array $market The market config.
	 *
	 * @return string
	 */
	private function get_market_currency( array $market ): string {
		$currency = is_array( $market['currency'] ?? null )
			? (string) ( $market['currency'][0] ?? '' )
			: (string) ( $market['currency'] ?? '' );

		return '' === $currency ? get_woocommerce_currency() : $currency;
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
	 * language list. Each secondary market contributes one language-currency
	 * derived label per configured language, each paired with that single
	 * language; a market with no configured languages contributes one
	 * site-language label paired with an empty list (matching every language).
	 *
	 * @return array<int, array{feed_label: string, languages: array}>
	 */
	private function get_market_labels_with_languages(): array {
		$markets = [
			[
				'feed_label' => $this->get_main_feed_label(),
				'languages'  => $this->get_existing_primary_languages(),
			],
		];

		foreach ( $this->get_stored_secondary_markets() as $market ) {
			$market          = $this->apply_site_locale_when_not_multilingual( $market );
			$base_feed_label = (string) ( $market['feed_label'] ?? '' );
			$currency        = $this->get_market_currency( $market );
			$languages       = is_array( $market['language'] ?? null ) ? $market['language'] : [];

			if ( empty( $languages ) ) {
				// A market with no configured languages accepts every product;
				// all its entries sync under the site-language label, so it
				// contributes that single label matching every language.
				$markets[] = [
					'feed_label' => $this->get_market_feed_label( $base_feed_label, '', $currency ),
					'languages'  => [],
				];
				continue;
			}

			foreach ( $languages as $language ) {
				$markets[] = [
					'feed_label' => $this->get_market_feed_label( $base_feed_label, (string) $language, $currency ),
					'languages'  => [ (string) $language ],
				];
			}
		}

		return $markets;
	}

	/**
	 * Returns every `google_ids` key a market's entries can be stored under:
	 * the stored base feed label, the superseded currency-only label, and one
	 * language-currency label per configured language.
	 *
	 * The base label and the currency-only label are always included so
	 * entries synced before the language component was added to the scheme
	 * are still found and cleaned up.
	 *
	 * @param string $feed_label The market's stored feed label.
	 * @param array  $languages  The market's configured language codes. An
	 *                           empty list contributes the site-language label.
	 * @param string $currency   The market's effective currency code.
	 *
	 * @return string[]
	 */
	private function get_market_feed_label_variants( string $feed_label, array $languages, string $currency ): array {
		if ( '' === $currency ) {
			$currency = get_woocommerce_currency();
		}

		$variants = [
			$feed_label,
			$feed_label . '-' . strtoupper( $currency ),
		];

		$languages = empty( $languages ) ? [ '' ] : $languages;
		foreach ( $languages as $language ) {
			$variants[] = $this->get_market_feed_label( $feed_label, (string) $language, $currency );
		}

		return array_values( array_unique( $variants ) );
	}

	/**
	 * Returns the current derived feed label set for a market config: one
	 * language-currency label per configured language (the site-language
	 * label when no languages are configured).
	 *
	 * @param string $feed_label The market's stored feed label.
	 * @param array  $market     The market config the languages and currency come from.
	 *
	 * @return string[]
	 */
	private function get_market_derived_feed_labels( string $feed_label, array $market ): array {
		$languages = is_array( $market['language'] ?? null ) && ! empty( $market['language'] )
			? $market['language']
			: [ '' ];

		$currency = $this->get_market_currency( $market );
		$labels   = [];

		foreach ( $languages as $language ) {
			$labels[] = $this->get_market_feed_label( $feed_label, (string) $language, $currency );
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
	 * Returns every country that should receive a Merchant Center shipping
	 * service: the primary market's target countries plus each non-manual
	 * secondary market's country.
	 *
	 * Manual markets are excluded because their shipping is managed outside
	 * the plugin, mirroring the per-market currency map in the Google
	 * Settings service.
	 *
	 * @return string[]
	 */
	public function get_shipping_sync_countries(): array {
		$countries = $this->target_audience->get_target_countries();

		foreach ( $this->get_stored_secondary_markets() as $market ) {
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
	 * Returns true when at least one market has a non-`manual` `shipping_rate`
	 * combined with `shipping_time === 'flat'`. A non-`manual` secondary market
	 * is enough to require a sync even when the primary itself is `manual`.
	 *
	 * @return bool
	 */
	public function has_syncable_markets(): bool {
		foreach ( $this->get_markets() as $market ) {
			$rate = $market['shipping_rate'] ?? null;
			$time = $market['shipping_time'] ?? null;
			if ( 'manual' !== $rate && 'flat' === $time ) {
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
		$market['currency'] = [ $this->get_site_primary_currency() ];

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
