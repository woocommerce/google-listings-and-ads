<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use DateTime;
use WC_DateTime;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPML
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WPML implements IntegrationInterface {

	/**
	 * Returns whether the integration is active or not.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * Initializes the integration (e.g. by registering the required hooks, filters, etc.).
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Returns whether product prices can be converted into another currency.
	 *
	 * This is the exact availability condition used by the price conversion
	 * methods below: WPML active with WCML multi-currency enabled. Markets
	 * priced in a non-store currency can only be synced when this holds.
	 *
	 * @return bool
	 */
	public function can_convert_currency(): bool {
		return $this->is_active() && $this->is_wcml_multi_currency_on();
	}

	/**
	 * Runs the given callback with WPML switched to the "all languages" context.
	 *
	 * WPML restricts every post query to the current language, so any product query
	 * the plugin runs in a background job (sync, delete, cleanup, stale detection,
	 * disconnect) would otherwise only ever see one language's products. Switching to
	 * the reserved `all` language code makes WPML emit an all-languages WHERE clause
	 * and stop translating queried IDs, so the plugin operates on every translation.
	 *
	 * When WPML is not active the callback is simply invoked, leaving single-language
	 * and non-multilingual sites behaving exactly as before.
	 *
	 * @template T
	 *
	 * @param callable():T $callback The work to run in the all-languages context.
	 *
	 * @return T The callback's return value.
	 */
	public function run_in_all_languages( callable $callback ) {
		if ( ! $this->is_active() ) {
			return $callback();
		}

		$previous_language = apply_filters( 'wpml_current_language', null );

		do_action( 'wpml_switch_language', 'all' );

		try {
			return $callback();
		} finally {
			// Restore the language that was active before we switched.
			do_action( 'wpml_switch_language', $previous_language );
		}
	}

	/**
	 * Returns the site's default WPML language code.
	 *
	 * @return string ISO 639-1 language code, or empty when unavailable.
	 */
	public function get_default_language_code(): string {
		if ( ! $this->is_active() ) {
			return '';
		}

		$default = apply_filters( 'wpml_default_language', null );

		return is_string( $default ) && '' !== $default ? $default : '';
	}

	/**
	 * Returns the WPML language code for a given post ID.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The language code, or empty string when WPML is inactive or no language is recorded.
	 */
	public function get_post_language( int $post_id ): string {
		if ( ! $this->is_active() ) {
			return '';
		}

		$details = apply_filters( 'wpml_post_language_details', null, $post_id );

		if ( ! is_array( $details ) ) {
			return '';
		}

		$code = $details['language_code'] ?? '';

		return is_string( $code ) ? $code : '';
	}

	/**
	 * Returns the store's active WPML languages.
	 *
	 * @return array<int, array{code: string, label: string}>
	 */
	public function get_languages(): array {
		if ( ! $this->is_active() ) {
			return [];
		}

		$languages = apply_filters( 'wpml_active_languages', null, null );

		if ( ! is_array( $languages ) ) {
			return [];
		}

		$result = [];

		foreach ( $languages as $code => $language ) {
			if ( ! is_string( $code ) || ! is_array( $language ) ) {
				continue;
			}

			$label = $this->get_language_label( $language );

			if ( '' === $label ) {
				continue;
			}

			$result[] = [
				'code'  => $code,
				'label' => $label,
			];
		}

		return $result;
	}

	/**
	 * Returns the store's active currencies from WCML when multi-currency is enabled,
	 * or the WooCommerce store currency as a single entry otherwise.
	 *
	 * Each currency carries the active language codes it is enabled for, per WCML's
	 * per-language currency configuration. When WCML multi-currency is off, every
	 * currency lists all active language codes.
	 *
	 * @return array<int, array{code: string, symbol: string, languages: string[]}>
	 */
	public function get_currencies(): array {
		if ( ! $this->is_active() ) {
			return [];
		}

		return $this->get_formatted_currencies( $this->get_active_currency_codes() );
	}

	/**
	 * Returns the subset of the given currency codes that are enabled for a language.
	 *
	 * With WCML off or no per-language config, every currency is enabled (single-currency and
	 * non-WCML stores are unaffected); the `woocommerce_gla_language_currencies` filter overrides.
	 *
	 * @param string[] $currencies Currency codes to filter.
	 * @param string   $language   ISO 639-1 language code.
	 *
	 * @return string[] The enabled subset.
	 */
	public function get_currencies_enabled_for_language( array $currencies, string $language ): array {
		$enabled = $this->filter_currencies_for_language( $currencies, $this->get_wcml_currency_options(), $language );

		/**
		 * Filters the currencies enabled for a language.
		 *
		 * @param string[] $enabled    Currency codes enabled for the language.
		 * @param string   $language   Language code.
		 * @param string[] $currencies The currency codes being filtered.
		 */
		$enabled = apply_filters( 'woocommerce_gla_language_currencies', $enabled, $language, $currencies );

		return is_array( $enabled ) ? array_values( array_unique( $enabled ) ) : [];
	}

	/**
	 * Returns the regular price for a product converted into the given currency via WCML.
	 *
	 * @param WC_Product $product
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return float|null Converted regular price, or null when WPML is inactive, WCML multi-currency
	 *                    is off, or the product has no regular price.
	 */
	public function get_product_price_in_currency( WC_Product $product, string $currency ): ?float {
		if ( ! $this->is_active() || ! $this->is_wcml_multi_currency_on() ) {
			return null;
		}

		if ( ! $this->is_active_currency( $currency ) ) {
			return null;
		}

		$custom_prices = $this->get_wcml_custom_prices( (int) $product->get_id(), $currency );
		if ( false !== $custom_prices ) {
			return isset( $custom_prices['_regular_price'] ) && '' !== $custom_prices['_regular_price']
				? (float) $custom_prices['_regular_price']
				: null;
		}

		$regular_price = $product->get_regular_price();
		if ( '' === $regular_price ) {
			return null;
		}

		return (float) apply_filters( 'wcml_raw_price_amount', (float) $regular_price, $currency );
	}

	/**
	 * Returns the sale price for a product converted into the given currency via WCML.
	 *
	 * @param WC_Product $product
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return float|null Converted sale price, or null when WPML is inactive, WCML multi-currency
	 *                    is off, or the product has no sale price.
	 */
	public function get_product_sale_price_in_currency( WC_Product $product, string $currency ): ?float {
		if ( ! $this->is_active() || ! $this->is_wcml_multi_currency_on() ) {
			return null;
		}

		if ( ! $this->is_active_currency( $currency ) ) {
			return null;
		}

		$custom_prices = $this->get_wcml_custom_prices( (int) $product->get_id(), $currency );
		if ( false !== $custom_prices ) {
			// Manual mode: WCML's get_product_custom_prices has already applied any
			// per-currency or base sale-date range. An empty _sale_price means the sale
			// is not currently active for this currency.
			return isset( $custom_prices['_sale_price'] ) && '' !== $custom_prices['_sale_price']
				? (float) $custom_prices['_sale_price']
				: null;
		}

		$sale_price = $product->get_sale_price();
		if ( '' === $sale_price ) {
			return null;
		}

		// Auto mode: WCML's filter only performs exchange-rate conversion; it does not
		// apply the base WC sale-end date. Honour that date here so an expired base sale
		// is not auto-converted and emitted in the override currency.
		$sale_end_date = $product->get_date_on_sale_to();
		if ( ! empty( $sale_end_date ) && $sale_end_date < new WC_DateTime() ) {
			return null;
		}

		return (float) apply_filters( 'wcml_raw_price_amount', (float) $sale_price, $currency );
	}

	/**
	 * Returns the sale effective-date string for a product in a given currency, or null
	 * when no per-currency dates are configured (the caller should fall back to base
	 * WC sale dates in that case).
	 *
	 * WCML stores per-currency sale dates as Unix timestamps under post meta keys
	 * `_sale_price_dates_from_{currency}` and `_sale_price_dates_to_{currency}`.
	 *
	 * @param WC_Product $product
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return string|null
	 */
	public function get_product_sale_dates_in_currency( WC_Product $product, string $currency ): ?string {
		if ( ! $this->is_active() || ! $this->is_wcml_multi_currency_on() ) {
			return null;
		}

		$from_meta = get_post_meta( $product->get_id(), '_sale_price_dates_from_' . $currency, true );
		$to_meta   = get_post_meta( $product->get_id(), '_sale_price_dates_to_' . $currency, true );

		if ( ! $from_meta && ! $to_meta ) {
			return null;
		}

		$from = $from_meta ? gmdate( DateTime::ATOM, (int) $from_meta ) : '';
		$to   = $to_meta ? gmdate( DateTime::ATOM, (int) $to_meta ) : '';

		return sprintf( '%s/%s', $from, $to );
	}

	/**
	 * Converts a store-currency amount into the given currency via WCML.
	 *
	 * Used for plain amounts with no product attached, such as shipping rates
	 * and free-shipping thresholds; the product price converters above handle
	 * per-product manual prices and sale dates.
	 *
	 * @param float  $amount   Amount in the store currency.
	 * @param string $currency ISO 4217 currency code to convert into.
	 *
	 * @return float|null Converted amount, or null when WPML is inactive or
	 *                    WCML multi-currency is off.
	 */
	public function convert_amount( float $amount, string $currency ): ?float {
		if ( ! $this->is_active() || ! $this->is_wcml_multi_currency_on() ) {
			return null;
		}

		if ( ! $this->is_active_currency( $currency ) ) {
			return null;
		}

		return (float) apply_filters( 'wcml_raw_price_amount', $amount, $currency );
	}

	/**
	 * Whether WCML can convert amounts into the given currency: it must be one
	 * of WCML's active currencies. WCML's conversion filter returns 0 for a
	 * currency it does not have active, so converting into an inactive
	 * currency must read as unavailable, never as a zero amount.
	 *
	 * @param string $currency ISO 4217 currency code.
	 *
	 * @return bool
	 */
	protected function is_active_currency( string $currency ): bool {
		return in_array( $currency, $this->get_active_currency_codes(), true );
	}

	/**
	 * Returns WCML currency codes when multi-currency is enabled, or the single WooCommerce
	 * store currency as a fallback when WCML multi-currency is off or unavailable.
	 *
	 * @return string[]
	 */
	protected function get_active_currency_codes(): array {
		if ( $this->is_wcml_multi_currency_on() ) {
			global $woocommerce_wpml;

			if ( isset( $woocommerce_wpml ) && is_object( $woocommerce_wpml ) && method_exists( $woocommerce_wpml, 'get_multi_currency' ) ) {
				$multi_currency = $woocommerce_wpml->get_multi_currency();

				if ( is_object( $multi_currency ) && method_exists( $multi_currency, 'get_currency_codes' ) ) {
					$codes = $multi_currency->get_currency_codes();

					return is_array( $codes ) ? array_values( $codes ) : [];
				}
			}
		}

		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';

		return is_string( $currency ) && '' !== $currency ? [ $currency ] : [];
	}

	/**
	 * Returns whether WCML multi-currency is enabled.
	 *
	 * @return bool
	 */
	protected function is_wcml_multi_currency_on(): bool {
		return function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on();
	}

	/**
	 * Narrows currency codes to those enabled for a language via WCML's per-currency options. No
	 * options means all enabled; otherwise a currency is enabled unless its `languages` map disables
	 * the language (no map means enabled everywhere).
	 *
	 * @param string[]                            $active   Active currency codes.
	 * @param array<string, array<string, mixed>> $options  WCML currency options keyed by code.
	 * @param string                              $language Language code.
	 *
	 * @return string[]
	 */
	protected function filter_currencies_for_language( array $active, array $options, string $language ): array {
		if ( empty( $options ) ) {
			return array_values( $active );
		}

		$enabled = [];

		foreach ( $active as $code ) {
			$languages = $options[ $code ]['languages'] ?? null;

			if ( ! is_array( $languages ) || ! array_key_exists( $language, $languages ) || ! empty( $languages[ $language ] ) ) {
				$enabled[] = $code;
			}
		}

		return $enabled;
	}

	/**
	 * Returns WCML's per-currency options keyed by code (each may carry a per-language enablement
	 * map under `languages`), or empty when WCML multi-currency is off or unavailable.
	 *
	 * Shape `currency_options[code]['languages'][lang]` (1 enabled, 0 disabled, absent = enabled)
	 * matches the WCML source but is not verified against a live install; the
	 * `woocommerce_gla_language_currencies` filter overrides if it differs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_wcml_currency_options(): array {
		if ( ! $this->is_active() || ! $this->is_wcml_multi_currency_on() ) {
			return [];
		}

		global $woocommerce_wpml;

		if ( ! isset( $woocommerce_wpml ) || ! is_object( $woocommerce_wpml ) ) {
			return [];
		}

		$options = null;

		if ( method_exists( $woocommerce_wpml, 'get_setting' ) ) {
			$options = $woocommerce_wpml->get_setting( 'currency_options' );
		} elseif ( isset( $woocommerce_wpml->settings ) && is_array( $woocommerce_wpml->settings ) ) {
			$options = $woocommerce_wpml->settings['currency_options'] ?? null;
		}

		return is_array( $options ) ? $options : [];
	}

	/**
	 * Returns WCML's manual per-currency prices for a product, or false when manual prices
	 * are not configured for that product+currency.
	 *
	 * Wraps WCML's own custom-prices lookup so callers can prefer manual values when set,
	 * and fall back to the wcml_raw_price_amount auto-conversion filter otherwise.
	 *
	 * @param int    $product_id
	 * @param string $currency
	 *
	 * @return array<string, string>|false
	 */
	protected function get_wcml_custom_prices( int $product_id, string $currency ) {
		global $woocommerce_wpml;

		if ( ! isset( $woocommerce_wpml ) || ! is_object( $woocommerce_wpml ) ) {
			return false;
		}

		if ( ! isset( $woocommerce_wpml->multi_currency ) || ! isset( $woocommerce_wpml->multi_currency->custom_prices ) ) {
			return false;
		}

		$custom_prices = $woocommerce_wpml->multi_currency->custom_prices;

		if ( ! is_object( $custom_prices ) || ! method_exists( $custom_prices, 'get_product_custom_prices' ) ) {
			return false;
		}

		return $custom_prices->get_product_custom_prices( $product_id, $currency );
	}

	/**
	 * Returns the active language codes a currency is enabled for, according to WCML's
	 * per-language currency configuration ("Currencies to display for each language").
	 *
	 * WCML stores the configuration as an integer flag per language code. A language
	 * with no stored flag counts as enabled, matching WCML's own default for newly
	 * added languages. Flags for languages that are no longer active are ignored.
	 *
	 * @param string   $currency              ISO 4217 currency code.
	 * @param string[] $active_language_codes Active WPML language codes.
	 *
	 * @return string[]
	 */
	protected function get_enabled_language_codes_for_currency( string $currency, array $active_language_codes ): array {
		$flags = $this->get_wcml_settings()['currency_options'][ $currency ]['languages'] ?? [];

		if ( ! is_array( $flags ) ) {
			$flags = [];
		}

		$enabled = [];

		foreach ( $active_language_codes as $language_code ) {
			if ( ! isset( $flags[ $language_code ] ) || $flags[ $language_code ] ) {
				$enabled[] = $language_code;
			}
		}

		return $enabled;
	}

	/**
	 * Returns WCML's stored settings, or an empty array when unavailable.
	 *
	 * @return array
	 */
	protected function get_wcml_settings(): array {
		global $woocommerce_wpml;

		if ( ! isset( $woocommerce_wpml ) || ! is_object( $woocommerce_wpml ) || ! method_exists( $woocommerce_wpml, 'get_settings' ) ) {
			return [];
		}

		$settings = $woocommerce_wpml->get_settings();

		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * @param string[] $codes
	 *
	 * @return array<int, array{code: string, symbol: string, languages: string[]}>
	 */
	private function get_formatted_currencies( array $codes ): array {
		if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return [];
		}

		$active_language_codes = array_column( $this->get_languages(), 'code' );
		$multi_currency_on     = $this->is_wcml_multi_currency_on();

		$result = [];

		foreach ( $codes as $code ) {
			if ( ! is_string( $code ) || '' === $code ) {
				continue;
			}

			$symbol = get_woocommerce_currency_symbol( $code );

			if ( ! is_string( $symbol ) || '' === $symbol ) {
				continue;
			}

			$result[] = [
				'code'      => $code,
				'symbol'    => html_entity_decode( $symbol, ENT_QUOTES, 'UTF-8' ),
				'languages' => $multi_currency_on
					? $this->get_enabled_language_codes_for_currency( $code, $active_language_codes )
					: $active_language_codes,
			];
		}

		return $result;
	}

	/**
	 * Resolves the display label for a WPML language entry.
	 *
	 * @param array<string, mixed> $language
	 *
	 * @return string
	 */
	private function get_language_label( array $language ): string {
		foreach ( [ 'translated_name', 'native_name', 'display_name' ] as $key ) {
			if ( ! empty( $language[ $key ] ) && is_string( $language[ $key ] ) ) {
				return $language[ $key ];
			}
		}

		return '';
	}
}
