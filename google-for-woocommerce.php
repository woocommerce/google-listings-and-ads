<?php
/**
 * Plugin Name: Google for WooCommerce
 * Plugin URL: https://woocommerce.com/
 * Description:
 * Version:
 * Author: Automattic
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

defined( 'ABSPATH' ) || exit;

/**
 * Register the JS.
 */
function add_extension_register_script() {

	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_page() ) {
		return;
	}

	$script_path       = '/js/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/js/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require( $script_asset_path )
		: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
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
		// Add any dependencies styles may have, such as wp-components.
		array(),
		filemtime( dirname( __FILE__ ) . '/js/build/index.css' )
	);

	wp_enqueue_script( 'google-for-woocommerce' );
	wp_enqueue_style( 'google-for-woocommerce' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );
