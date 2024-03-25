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
	function( $old_config ) {
		return "gtag( 'consent', 'default', {
			analytics_storage: 'granted',
			ad_storage: 'granted',
			ad_user_data: 'granted',
			ad_personalization: 'granted',
		} );
		";
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
