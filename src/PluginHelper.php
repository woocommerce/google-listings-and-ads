<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

/**
 * Trait PluginHelper
 *
 * Helper functions that are useful throughout the plugin.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
trait PluginHelper {

	/**
	 * Get the root directory for the plugin.
	 *
	 * @return string
	 */
	protected function get_root_dir(): string {
		return dirname( __DIR__ );
	}

	/**
	 * Get the full path to the main plugin file.
	 *
	 * @return string
	 */
	protected function get_main_file(): string {
		return "{$this->get_root_dir()}/{$this->get_main_filename()}";
	}

	/**
	 * Get the main file for this plugin.
	 *
	 * @return string
	 */
	protected function get_main_filename(): string {
		return 'google-listings-and-ads.php';
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	protected function get_slug(): string {
		// This value is also hard-coded in uninstall.php
		return 'gla';
	}

	/**
	 * Get the plugin URL, possibly with an added path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function get_plugin_url( string $path = '' ): string {
		return plugins_url( $path, $this->get_main_file() );
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	protected function get_version(): string {
		return WC_GLA_VERSION;
	}

	/**
	 * Get the prefix used for plugin's metadata keys in the database.
	 *
	 * @return string
	 */
	protected function get_meta_key_prefix(): string {
		// This value is also hard-coded in uninstall.php
		return "_wc_{$this->get_slug()}";
	}

	/**
	 * Prefix a meta data key with the plugin prefix.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function prefix_meta_key( string $key ): string {
		$prefix = $this->get_meta_key_prefix();

		return "{$prefix}_{$key}";
	}

	/**
	 * Get the plugin basename
	 *
	 * @return string
	 */
	protected function get_plugin_basename(): string {
		return plugin_basename( $this->get_main_file() );
	}

	/**
	 * Get the plugin start URL
	 *
	 * @return string
	 */
	protected function get_start_url(): string {
		return admin_url( 'admin.php?page=wc-admin&path=/google/start' );
	}

	/**
	 * Get the URL to connect an Ads account
	 *
	 * @return string
	 */
	protected function get_setup_ads_url(): string {
		return admin_url( 'admin.php?page=wc-admin&path=/google/setup-ads' );
	}

	/**
	 * Get the plugin settings URL
	 *
	 * @return string
	 */
	protected function get_settings_url(): string {
		return admin_url( 'admin.php?page=wc-admin&path=/google/settings' );
	}

	/**
	 * Get the coupon list view URL
	 *
	 * @return string
	 */
	protected function get_coupons_url(): string {
		return admin_url( 'edit.php?post_type=shop_coupon' );
	}

	/**
	 * Get the plugin documentation URL
	 *
	 * @return string
	 */
	protected function get_documentation_url(): string {
		return 'https://docs.woocommerce.com/document/google-listings-and-ads/';
	}

	/**
	 * Check whether debugging mode is enabled.
	 *
	 * @return bool Whether debugging mode is enabled.
	 */
	protected function is_debug_mode(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Get the WooCommerce Connect Server URL
	 *
	 * @return string
	 */
	protected function get_connect_server_url(): string {
		if ( defined( 'WOOCOMMERCE_GLA_CONNECT_SERVER_URL' ) ) {
			return apply_filters( 'woocommerce_gla_wcs_url', WOOCOMMERCE_GLA_CONNECT_SERVER_URL );
		}

		// TODO: Change to api.woocommerce.com when we are no longer in test phase.
		return apply_filters( 'woocommerce_gla_wcs_url', 'https://api-vipgo.woocommerce.com' );
	}

	/**
	 * Gets the main site URL which is used for the home page.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	protected function get_site_url(): string {
		return apply_filters( 'woocommerce_gla_site_url', get_home_url() );
	}

	/**
	 * Removes the protocol (http:// or https://) and trailing slash from the provided URL.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function strip_url_protocol( string $url ): string {
		return preg_replace( '#^https?://#', '', untrailingslashit( $url ) );
	}

}
