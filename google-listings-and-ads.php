<?php
/**
 * Plugin Name: Google Listings and Ads
 * Plugin URL: https://woocommerce.com/
 * Description: Native integration with Google that allows merchants to easily display their products across Google’s network.
 * Version: 0.1.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: google-listings-and-ads
 * Requires at least: 5.3
 * Requires PHP: 7.1
 *
 * WC requires at least: 4.7
 * WC tested up to: 4.7
 * Woo:
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\GoogleListingsAndAds\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Autoloader;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\ConnectionTest;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

// Load and initialize the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! Autoloader::init() ) {
	return;
}

// Hook much of our plugin after WooCommerce is loaded.
add_action(
	'woocommerce_loaded',
	function () {
		PluginFactory::instance()->register();
	}
);

/**
 * Get out main container object.
 *
 * @return ContainerInterface
 */
function woogle_get_container() : ContainerInterface {
	static $container = null;
	if ( null === $container ) {
		$container = new Container();
	}

	return $container;
}

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
		'google-listings-and-ads',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'google-listings-and-ads',
		plugins_url( '/js/build/index.css', __FILE__ ),
		defined( 'WC_ADMIN_PLUGIN_FILE' ) ? [ 'wc-admin-app' ] : [],
		filemtime( dirname( __FILE__ ) . '/js/build/index.css' )
	);

	if ( wc_admin_is_registered_page() ) {

		// Load WC Admin styles before our own styles
		if ( defined( 'WC_ADMIN_APP' ) ) {
			wp_enqueue_style( WC_ADMIN_APP );
		}

		wp_enqueue_script( 'google-listings-and-ads' );
		wp_enqueue_style( 'google-listings-and-ads' );

		// Demo tracks event
		do_action( 'woogle_extension_loaded' );
	}
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );

/**
 * Jetpack-config will initialize the modules on "plugins_loaded" with priority 2,
 * so this code needs to be run before that.
 */
add_action(
	'plugins_loaded',
	function() {
		$jetpack_config = new Automattic\Jetpack\Config();
		$jetpack_config->ensure(
			'connection',
			array(
				'slug' => 'connection-test',
				'name' => __( 'Connection Test', 'google-listings-and-ads' ),
			)
		);
	},
	1
);

/**
 * Initialize plugin after WooCommerce has a chance to initialize its packages.
 */
add_action(
	'plugins_loaded',
	function() {
		if ( class_exists( 'WooCommerce' ) ) {

			if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
				define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'http://localhost:5000' );
			}

			ConnectionTest::init();
		}
	},
);
