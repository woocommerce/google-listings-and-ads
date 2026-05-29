<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

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
	 * @return array<int, array{code: string, symbol: string}>
	 */
	public function get_currencies(): array {
		if ( ! $this->is_active() ) {
			return [];
		}

		return $this->get_formatted_currencies( $this->get_active_currency_codes() );
	}

	/**
	 * Returns WCML currency codes when multi-currency is enabled, or the single WooCommerce
	 * store currency as a fallback when WCML multi-currency is off or unavailable.
	 *
	 * @return string[]
	 */
	protected function get_active_currency_codes(): array {
		if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
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
	 * @param string[] $codes
	 *
	 * @return array<int, array{code: string, symbol: string}>
	 */
	private function get_formatted_currencies( array $codes ): array {
		if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return [];
		}

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
				'code'   => $code,
				'symbol' => html_entity_decode( $symbol, ENT_QUOTES, 'UTF-8' ),
			];
		}

		return $result;
	}

	/**
	 * Returns the price of a product converted to a specific currency via WCML.
	 *
	 * Returns null when:
	 * - WPML is not active
	 * - WCML multi-currency is not enabled
	 * - The product has no price set
	 *
	 * Returns 0.0 for legitimately free products — callers should not treat 0.0 as a fallback signal.
	 *
	 * @since 2.9.0
	 *
	 * @param WC_Product $product  The WooCommerce product to price.
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return float|null The converted price, or null when unavailable.
	 */
	public function get_product_price_in_currency( WC_Product $product, string $currency ): ?float {
		if ( ! $this->is_active() ) {
			return null;
		}

		if ( ! function_exists( 'wcml_is_multi_currency_on' ) || ! wcml_is_multi_currency_on() ) {
			return null;
		}

		$price = $product->get_price();
		if ( '' === $price ) {
			return null;
		}

		return (float) apply_filters( 'wcml_raw_price_amount', $price, $currency, null );
	}

	/**
	 * Returns the scheduled sale price of a product converted to a specific currency via WCML.
	 *
	 * Uses `$product->get_sale_price()` (the scheduled/stored sale price), not the active
	 * price, so it is safe to call regardless of whether the sale is currently active.
	 *
	 * Returns null when:
	 * - WPML is not active
	 * - WCML multi-currency is not enabled
	 * - The product has no scheduled sale price set
	 *
	 * @since 2.9.0
	 *
	 * @param WC_Product $product  The WooCommerce product to price.
	 * @param string     $currency ISO 4217 currency code.
	 *
	 * @return float|null The converted sale price, or null when unavailable.
	 */
	public function get_product_sale_price_in_currency( WC_Product $product, string $currency ): ?float {
		if ( ! $this->is_active() ) {
			return null;
		}

		if ( ! function_exists( 'wcml_is_multi_currency_on' ) || ! wcml_is_multi_currency_on() ) {
			return null;
		}

		$sale_price = $product->get_sale_price();
		if ( '' === $sale_price ) {
			return null;
		}

		return (float) apply_filters( 'wcml_raw_price_amount', $sale_price, $currency, null );
	}

	/**
	 * Returns the WooCommerce product translated into the given language, or null when
	 * no translation exists or WPML is inactive.
	 *
	 * @since 2.9.0
	 *
	 * @param WC_Product $product  The source product (any language).
	 * @param string     $language ISO 639-1 language code (e.g. 'fr').
	 *
	 * @return WC_Product|null The translated product, or null when unavailable.
	 */
	public function get_product_in_language( WC_Product $product, string $language ): ?WC_Product {
		if ( ! $this->is_active() ) {
			return null;
		}

		$translated_id = (int) apply_filters( 'wpml_object_id', $product->get_id(), 'product', false, $language );

		if ( ! $translated_id || $translated_id === $product->get_id() ) {
			return null;
		}

		$translated = wc_get_product( $translated_id );

		return $translated instanceof WC_Product ? $translated : null;
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
