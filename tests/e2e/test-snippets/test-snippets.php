<?php
/**
 * Plugin name: GLA Test Snippets
 * Description: A plugin to provide some PHP snippets used in E2E tests.
 *
 * Intended to function as a plugin while tests are running.
 * It hopefully goes without saying, this should not be used in a production environment.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Snippets;

/*
 * Customize/disable the gtag consent mode, to make testing easier by granting everything by default.
 * It's a hack to avoid specifying region for E2E environment, but it tests the customization of consent mode.
 */
add_filter(
	'woocommerce_gla_gtag_consent',
	function ( $old_config ) {
		$status = 'granted';
		// Optional: Set the default consent state for tests via the `consent_default` URL parameter.
		if ( isset( $_GET['consent_default'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_text_field( wp_unslash( $_GET['consent_default'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return sprintf( 'gtag( "consent", "default", {
			analytics_storage: "%1$s",
			ad_storage: "%1$s",
			ad_user_data: "%1$s",
			ad_personalization: "%1$s",
		} );', $status);
	}
);

/*
 * Declare an "optin" consent type for the WP Consent API, the same way a real
 * consent-management plugin (Complianz, CookieYes, etc.) would. Per the WP
 * Consent API's own docs, `wp_has_consent()` fails open (returns true for
 * every category, regardless of any cookie) whenever no plugin declares a
 * consent type — none of the CMPs it's designed to integrate with are
 * installed in this E2E environment, so without this, marketing-consent
 * gating (e.g. GOOGLE_CUSTOMER_REVIEWS's storefront scripts) could never be
 * tested as anything other than always-allowed.
 */
add_filter(
	'wp_get_consent_type',
	function () {
		return 'optin';
	}
);

/*
 * Mimic the `WooCommerceBrands` class to test plugin integration in product editor.
 */
add_filter(
	'woocommerce_gla_product_attribute_value_options_brand',
	function ( array $value_options ) {
		$value_options[ 'e2e_test_woocommerce_brands' ] = 'E2E test: From WooCommerce Brands';
		return $value_options;
	}
);

/**
 * Incremement PMax notifiation count.
 *
 * @param integer $current_count
 * @return integer
 */
add_filter(
	'google_for_woocommerce_admin_menu_notification_count',
	function( int $current_count ) {
		if ( isset( $_GET['no_notifications'] ) && $_GET['no_notifications'] === 'true' ) {
			return 0;
		}

		return ++$current_count;
	}
);
