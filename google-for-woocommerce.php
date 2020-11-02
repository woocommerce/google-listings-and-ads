<?php
/**
 * Plugin Name: Google for WooCommerce
 * Plugin URL: https://woocommerce.com/
 * Description: Native integration with Google that allows merchants to easily display their products across Google’s network.
 * Version: 0.1
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: google-for-woocommerce
 * Requires at least: 5.3
 * Requires PHP: 7:0
 *
 * WC requires at least: 4.3
 * WC tested up to: 4.6
 * Woo:
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Register the JS.
 */
function add_extension_register_script() {
	/*
	This if statement will need to be adjusted later. Simply disabled for now
	if ( ! class_exists( Loader::class ) || ! Loader::is_admin_page() ) {
		return;
	}
	*/

	$script_path       = '/js/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/js/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $script_path ),
		];

	$script_url = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'google-for-woocommerce',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'google-for-woocommerce',
		plugins_url( '/js/build/index.css', __FILE__ ),
		defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
		array(),
		filemtime( dirname( __FILE__ ) . '/js/build/index.css' )
	);

	// Load WC Admin styles before our own styles
	if ( defined( 'WC_ADMIN_APP' ) ) {
		wp_enqueue_style( WC_ADMIN_APP );
	}

	wp_enqueue_script( 'google-for-woocommerce' );
	wp_enqueue_style( 'google-for-woocommerce' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );
