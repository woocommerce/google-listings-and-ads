<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests;

use WC_Install;
use WP_Roles;

global $gla_dir;
global $wp_plugins_dir;
global $wc_dir;

if ( PHP_MAJOR_VERSION >= 8 ) {
	echo "The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: path_join( sys_get_temp_dir(), '/wordpress-tests-lib' );
validate_file_exits( "{$wp_tests_dir}/includes/functions.php" );

$wp_core_dir    = getenv( 'WP_CORE_DIR' ) ?: path_join( sys_get_temp_dir(), '/wordpress' );
$wp_plugins_dir = path_join( $wp_core_dir, '/wp-content/plugins' );

$gla_dir = dirname( __FILE__, 2 ); // ../../

$wc_dir = getenv( 'WC_DIR' );
if ( ! $wc_dir ) {
	// Check if WooCommerce exists in the core plugin folder. The `bin/install-wp-tests.sh` script clones a copy there.
	$wc_dir = path_join( $wp_plugins_dir, '/woocommerce' );
	if ( ! file_exists( "{$wc_dir}/woocommerce.php" ) ) {
		// Check if WooCommerce exists in parent directory of the plugin (in case the plugin is located in a WordPress installation's `wp-content/plugins` folder)
		$wc_dir = path_join( dirname( $gla_dir ), '/woocommerce' );
	}
}
validate_file_exits( "{$wc_dir}/woocommerce.php" );

// Require the composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once "{$wp_tests_dir}/includes/functions.php";

tests_add_filter( 'muplugins_loaded', function () {
	load_plugins();
} );

// Start up the WP testing environment.
require "{$wp_tests_dir}/includes/bootstrap.php";

install_woocommerce();

/**
 * Load WooCommerce for testing
 *
 * @global $wc_dir
 */
function install_woocommerce() {
	global $wc_dir;

	echo "Installing WooCommerce..." . PHP_EOL;

	define( 'WP_UNINSTALL_PLUGIN', true );

	include $wc_dir . '/uninstall.php';

	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_attribute_taxonomies" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_items" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_itemmeta" );

	WC_Install::install();

	new WP_Roles();

	WC()->init();

	// Include WooCommerce test helpers
	$wc_tests_dir = $wc_dir . '/tests';
	if ( file_exists( $wc_dir . '/tests/legacy/bootstrap.php' ) ) {
		$wc_tests_dir .= '/legacy';
	}
	require_once $wc_tests_dir . '/includes/wp-http-testcase.php';
	require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-product.php';
	require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-shipping.php';
	require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-customer.php';

	echo "WooCommerce installed!" . PHP_EOL;
}

/**
 * Manually load plugins
 *
 * @global $gla_dir
 * @global $wc_dir
 */
function load_plugins() {
	global $gla_dir;
	global $wc_dir;

	require_once( $wc_dir . '/woocommerce.php' );
	update_option( 'woocommerce_db_version', WC()->version );

	require $gla_dir . '/google-listings-and-ads.php';
}

/**
 * Checks whether a file exists and throws an error if it doesn't.
 *
 * @param string $file_name
 */
function validate_file_exits( string $file_name ) {
	if ( ! file_exists( $file_name ) ) {
		echo "Could not find {$file_name}, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit( 1 );
	}
}

/**
 * @param string $base
 * @param string $path
 *
 * @return string
 */
function path_join( string $base, string $path ) {
	return rtrim( $base, '/\\' ) . '/' . ltrim( $path, '/\\' );
}
