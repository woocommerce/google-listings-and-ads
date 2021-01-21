<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminScriptWithBuiltDependenciesAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AdminStyleAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsAwareness;
use Automattic\WooCommerce\GoogleListingsAndAds\Assets\AssetsHandlerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\BuiltScriptDependencyArray;

/**
 * Class Admin
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Pages
 */
class Admin implements Service, Registerable, Conditional {

	use AssetsAwareness, AdminConditional, PluginHelper;

	/**
	 * Admin constructor.
	 *
	 * @param AssetsHandlerInterface $assets_handler
	 */
	public function __construct( AssetsHandlerInterface $assets_handler ) {
		$this->assets_handler = $assets_handler;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_assets();

		add_action(
			'admin_enqueue_scripts',
			function() {
				if ( wc_admin_is_registered_page() ) {
					$this->enqueue_assets();
				}
			}
		);

		add_action(
			"plugin_action_links_{$this->get_plugin_basename()}",
			function( $links ) {
				return $this->add_plugin_links( $links );
			}
		);
	}

	/**
	 * Set up the array of assets.
	 */
	protected function setup_assets(): void {
		$this->assets[] = ( new AdminScriptWithBuiltDependenciesAsset(
			'google-listings-and-ads',
			'js/build/index',
			"{$this->get_root_dir()}/js/build/index.asset.php",
			new BuiltScriptDependencyArray(
				[
					'dependencies' => [],
					'version'      => filemtime( "{$this->get_root_dir()}/js/build/index.js" ),
				]
			)
		) )->add_localization(
			'glaData',
			[
				'placeholder' => 'placeholder',
			]
		);

		$this->assets[] = ( new AdminStyleAsset(
			'google-listings-and-ads-css',
			'/js/build/index',
			defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
			(string) filemtime( "{$this->get_root_dir()}/js/build/index.css" )
		) );
	}

	/**
	 * Adds links to the plugin's row in the "Plugins" wp-admin page.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
	 * @param array $links The existing list of links that will be rendered.
	 */
	protected function add_plugin_links( $links ): array {
		$plugin_links = [];

		// If setup complete, add settings link.
		// $plugin_links[] = '<a href="' . esc_attr( $this->get_settings_url() ) . '">' . esc_html__( 'Settings', 'google-listings-and-ads' ) . '</a>';

		$plugin_links[] = '<a href="' . esc_attr( $this->get_documentation_url() ) . '">' . esc_html__( 'Documentation', 'google-listings-and-ads' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}
}
