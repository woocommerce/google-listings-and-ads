<?php
/**
 * Plugin Name: Google Listings and Ads
 * Plugin URL: https://wordpress.org/plugins/google-listings-and-ads/
 * Description: Native integration with Google that allows merchants to easily display their products across Google’s network.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: google-listings-and-ads
 * Requires at least: 5.3
 * Requires PHP: 7.3
 *
 * WC requires at least: 4.5
 * WC tested up to: 5.4
 * Woo:
 *
 * @package WooCommerce\Admin
 */

use Automattic\Jetpack\Config;
use Automattic\WooCommerce\GoogleListingsAndAds\Container;
use Automattic\WooCommerce\GoogleListingsAndAds\Autoloader;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginFactory;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements\VersionValidator;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

define( 'GLA_VERSION', '1.0.0' ); // WRCS: DEFINED_VERSION.

// Load and initialize the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! Autoloader::init() ) {
	return;
}

// Validate the required versions of everything our plugin depends on.
if ( ! VersionValidator::instance()->validate() ) {
	return;
}

// Register activation hook
register_activation_hook(
	__FILE__,
	function () {
		PluginFactory::instance()->activate();
	}
);

// Hook much of our plugin after WooCommerce is loaded.
add_action(
	'woocommerce_loaded',
	function () {
		PluginFactory::instance()->register();
	},
	1
);

// Register deactivation hook
register_deactivation_hook(
	__FILE__,
	function () {
		PluginFactory::instance()->deactivate();
	}
);

/**
 * Get our main container object.
 *
 * @return ContainerInterface
 */
function woogle_get_container(): ContainerInterface {
	static $container = null;
	if ( null === $container ) {
		$container = new Container();
	}

	return $container;
}

/**
 * Jetpack-config will initialize the modules on "plugins_loaded" with priority 2,
 * so this code needs to be run before that.
 */
add_action(
	'plugins_loaded',
	function() {
		woogle_get_container()->get( Config::class );
	},
	1
);
