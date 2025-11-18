<?php

/**
 * Plugin main file.
 *
 * @package   Google\GoogleTagGatewayExample
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://developers.google.com/tag-platform/tag-manager/gateway/setup-guide
 *
 * @wordpress-plugin
 * Plugin Name:       Example Google tag gateway for advertisers PHP library
 * Plugin URI:        https://developers.google.com/tag-platform/tag-manager/gateway/setup-guide
 * Description:       A simple WordPress plugin to show usage of the Google tag gateway PHP library.
 * Version:           1.0.0
 * Author:            Google
 * Author URI:        https://opensource.google.com
 * License:           Apache 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       gtg-ads-example
 */

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- WordPress plugin

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Load the plugin dependencies (which includes the Google tag gateway library)
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;
use Google\GoogleTagGatewayLibrary\Wordpress\Adapter as GtgAdapter;

const CONTAINER_ID = 'G-TEST';
const CUSTOM_MEASUREMENT_PATH = 'this/is/obviously/measurement';
const SCRIPT_HANDLE = 'gtg-scripts';

function gtgAdapter(?GtgAdapter $adapter = null): GtgAdapter
{
    static $instance;
    $instance = $adapter ?: $instance;
    if (! $instance) {
        throw new Error('Called before providing an instance of adapter');
    }
    return $instance;
}

gtgAdapter(GtgAdapter::create());

/** Set up the GTG environment on plugin activation */
function setup_gtg()
{
    gtgAdapter()->update([
        'tagId' => CONTAINER_ID,
        'measurementPath' => CUSTOM_MEASUREMENT_PATH,
    ]);
}
register_activation_hook(__FILE__, fn() => setup_gtg());

/**
 * On activation perform a health check to decide whether or not to enable the
 * plugin.
 */
function check_health_on_activation()
{
    $gtgHelper = new GoogleTagGatewayHelper(CONTAINER_ID);
    $healthCheck = $gtgHelper->healthCheck();

    if ($healthCheck['status'] === false) {
        update_option('health_check_error_message', $healthCheck['errorMessage']);
        update_option('health_check_failed', true);
    }
}

register_activation_hook(__FILE__, 'check_health_on_activation');

/** Check if the health check failed if so deactivate the plugin. */
function health_check_failed()
{
    if (get_option('health_check_failed')) {
        // Optionally disable the plugin if the health check does not pass.
        // deactivate_plugins(plugin_basename(__FILE__));
        // unset($_GET['activate']); // Disable WP "Plugin activated." message
        delete_option('health_check_failed');
    }
}
add_action('admin_init', 'health_check_failed');

/** If there is a health check error message display it in a banner. */
function health_check_error_message()
{
    $errorMessage = get_option('health_check_error_message');
    if (!empty($errorMessage)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . $errorMessage . '</p>';
        echo '</div>';
        delete_option('health_check_error_message');
    }
}
add_action('admin_notices', 'health_check_error_message');

/**
 * Inject script tags onto the page using the Google tag gateway library with
 * geo information forwarded through the script/plugin.
 */
function inject_script_with_geo()
{
    $geoJsFunctionImplementation = "
        function getGeoData() {
          return new Promise((res) => {
             setTimeout(() => res('US-CA'));
          });
        }
    ";
    $gtgHelper = new GoogleTagGatewayHelper(CONTAINER_ID, ['geoFunction' => 'getGeoData']);
    $resources = $gtgHelper->createResources();

    wp_register_script(SCRIPT_HANDLE, $resources['src'], false, null, false);
    wp_script_add_data(SCRIPT_HANDLE, 'script_execution', 'async');
    wp_enqueue_script(SCRIPT_HANDLE);

    wp_add_inline_script(SCRIPT_HANDLE, $resources['topScript'], 'before');

    wp_add_inline_script(SCRIPT_HANDLE, $geoJsFunctionImplementation);
    wp_add_inline_script(SCRIPT_HANDLE, $resources['script']);

    // Add an event for testing.
    wp_add_inline_script(SCRIPT_HANDLE, "gtag('event', 'wp-test-event', {});");
}

/**
 * Inject script tags onto the page depending on if the &include_geo query
 * parameter is present, then include a geo script as well.
 *
 * NOTE: Use only one of these options. Use the geo example if you have a geo
 * function implemented on page instead of the basic injection example.
 * This only serves as an example to show how each can be used.
 */
function inject_script()
{
    if (isset($_GET['include_geo'])) {
        add_action(
            'wp_enqueue_scripts',
            'inject_script_with_geo',
            1,
        );
    } else {
        gtgAdapter()->initialize();
    }
}

if (is_plugin_active('gtg-ads-example-plugin/gtg-ads-example.php')) {
    inject_script();
}
