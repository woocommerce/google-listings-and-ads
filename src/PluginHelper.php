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
		return GLA_VERSION;
	}

	/**
	 * Get the prefix used for plugin's metadata keys in the database.
	 *
	 * @return string
	 */
	protected function get_meta_key_prefix(): string {
		return '_wc_gla';
	}

	/**
	 * Prefix a meta data key with the plugin prefix.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function prefix_meta_key( string $key ): string {
		$prefix = self::get_meta_key_prefix();

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
	 * Get the plugin settings URL
	 *
	 * @return string
	 */
	protected function get_settings_url(): string {
		return admin_url( 'admin.php?page=wc-admin&path=/google/settings' );
	}

	/**
	 * Get the plugin documentation URL
	 *
	 * @return string
	 */
	protected function get_documentation_url(): string {
		return 'https://docs.woocommerce.com/document/google-listings-and-ads/';
	}
}
