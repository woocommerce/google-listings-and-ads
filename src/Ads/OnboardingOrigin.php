<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

defined( 'ABSPATH' ) || exit;

/** Tracks a Woo Ads handoff through the initiating administrator's onboarding. */
final class OnboardingOrigin {

	private const KEY_PREFIX = 'gla_woo_ads_origin_';

	/** Capture the marker only on the G4W dashboard handoff. */
	public static function capture(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! defined( 'WOO_ADS_FILE' ) ) {
			return;
		}

		// The marker is an attribution claim, not proof of which plugin initiated the request.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This navigation records only a temporary attribution hint.
		$page   = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$path   = isset( $_GET['path'] ) && is_string( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		$source = isset( $_GET['woo_ads_source'] ) && is_string( $_GET['woo_ads_source'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_ads_source'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( 'wc-admin' !== $page || '/google/dashboard' !== $path || 'woo_ads' !== $source ) {
			return;
		}

		// A finished Ads connection is not a new onboarding and must not arm a later one.
		if ( get_option( 'gla_ads_id' ) ) {
			self::clear();
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			set_transient( self::KEY_PREFIX . $user_id, 'woo_ads', DAY_IN_SECONDS );
		}
	}

	/** Return the WCS source header only while this user's handoff remains active. */
	public static function headers(): array {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || ! defined( 'WOO_ADS_FILE' ) ) {
			return [];
		}

		return 'woo_ads' === get_transient( self::KEY_PREFIX . $user_id )
			? [ 'x-woo-integration-source' => 'woo_ads' ]
			: [];
	}

	/** Clear the hint after the Ads account was successfully created or linked. */
	public static function clear(): void {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			delete_transient( self::KEY_PREFIX . $user_id );
		}
	}
}
