<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

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
