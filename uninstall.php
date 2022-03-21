<?php
/**
 * Google Listings and Ads Uninstall
 *
 * Uninstalling Google Listings and Ads unschedules any pending jobs, deletes its custom tables, related transients,
 * options, and product metadata.
 *
 * @since 1.3.0
 */

declare( strict_types=1 );

use Automattic\WooCommerce\GoogleListingsAndAds\Autoloader;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\TableManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Options;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/** @see \Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper::get_slug() */
define( 'WC_GLA_SLUG', 'gla' );

/** @see \Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper::get_meta_key_prefix() */
define( 'WC_GLA_METAKEY_PREFIX', '_wc_gla' );

/*
 * Only remove ALL plugin data if WC_GLA_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WC_GLA_REMOVE_ALL_DATA' ) && true === WC_GLA_REMOVE_ALL_DATA ) {
	// Load and initialize the autoloader.
	require_once __DIR__ . '/src/Autoloader.php';
	if ( ! Autoloader::init() ) {
		return;
	}

	global $wpdb;

	// un-schedule all ActionScheduler jobs for GLA
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( null, null, WC_GLA_SLUG );
	}

	// drop custom tables
	foreach ( TableManager::get_all_table_names() as $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . WC_GLA_SLUG . "_{$table}" );
	}

	// delete options
	foreach ( Options::get_all_option_keys() as $option ) {
		delete_option( WC_GLA_SLUG . "_{$option}" );
	}

	// delete transients
	foreach ( Transients::get_all_transient_keys() as $transient ) {
		delete_transient( WC_GLA_SLUG . "_{$transient}" );
	}

	// delete products metadata
	foreach ( ProductMetaHandler::get_all_meta_keys() as $meta_key ) {
		delete_post_meta_by_key( WC_GLA_METAKEY_PREFIX . "_{$meta_key}" );
	}

	// delete products attributes
	foreach ( AttributeManager::get_available_attribute_ids() as $attribute_id ) {
		delete_post_meta_by_key( WC_GLA_METAKEY_PREFIX . "_{$attribute_id}" );
	}
}
