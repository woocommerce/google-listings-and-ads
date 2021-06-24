<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests;

use WC_Install;
use WP_Roles;

if ( PHP_MAJOR_VERSION >= 8 ) {
	echo "The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?? rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
if ( ! file_exists( "{$wp_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$wp_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

global $gla_dir;
global $wp_plugins_dir;

$gla_dir        = dirname( __FILE__, 2 ); // ../../
$wp_plugins_dir = dirname( $gla_dir ); // ../

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
 * @global $wp_plugins_dir
 */
function install_woocommerce() {
	global $wp_plugins_dir;

	echo "Installing WooCommerce..." . PHP_EOL;

	define( 'WP_UNINSTALL_PLUGIN', true );

	include $wp_plugins_dir . '/woocommerce/uninstall.php';

	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_attribute_taxonomies" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_items" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_itemmeta" );

	WC_Install::install();

	new WP_Roles();

	WC()->init();

	// Include WooCommerce test helpers
	$wc_tests_dir = $wp_plugins_dir . '/woocommerce/tests';
	if ( file_exists( $wp_plugins_dir . '/woocommerce/tests/legacy/bootstrap.php' ) ) {
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
 * @global $wp_plugins_dir
 */
function load_plugins() {
	global $gla_dir;
	global $wp_plugins_dir;

	require_once( $wp_plugins_dir . '/woocommerce/woocommerce.php' );
	update_option( 'woocommerce_db_version', WC()->version );

	require $gla_dir . '/google-listings-and-ads.php';
}
