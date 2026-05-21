<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Class WpmlIntegration
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class WpmlIntegration implements IntegrationInterface {

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
