<?php
/**
 * Plugin name: GLA Test Data
 * Description: Utility intended to set test data on the site through a REST API request.
 *
 * Intended to function as a plugin while tests are running.
 * It hopefully goes without saying, this should not be used in a production environment.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\TestData;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );

/**
 * Register routes for setting test data.
 */
function register_routes() {
	register_rest_route(
		'wc/v3',
		'gla-test/conversion-id',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_conversion_id',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_conversion_id',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);
}

add_action( 'plugins_loaded', function () {

	if(isset($_GET['gla-e2e-onboarded'])){
		add_filter("woocommerce_gla_options_get_".OptionsInterface::REDIRECT_TO_ONBOARDING, function ($value){
			return 'no';
		});
		
		add_filter("woocommerce_gla_options_get_".OptionsInterface::MC_SETUP_COMPLETED_AT, function ($value){
			return 1693215209;
		});	
	
		add_filter("woocommerce_gla_options_get_".OptionsInterface::GOOGLE_CONNECTED, function ($value){
			return true;
		});
	}
} );

/**
 * Set the Ads Conversion Action to test values.
 */
function set_conversion_id() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::ADS_CONVERSION_ACTION,
		[
			'conversion_id'    => 'AW-123456',
			'conversion_label' => 'aB_cdEFgh',
		]
	);
}

/**
 * Clear a previously set Conversion Action.
 */
function clear_conversion_id() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->delete( OptionsInterface::ADS_CONVERSION_ACTION );	
}

/**
 * Check permissions for API requests.
 */
function permissions() {
	return true;
	return current_user_can( 'manage_woocommerce' );
}
