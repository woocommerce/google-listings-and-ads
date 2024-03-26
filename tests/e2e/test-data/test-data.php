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
	register_rest_route(
		'wc/v3',
		'gla-test/onboarded-merchant',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_onboarded_merchant',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_onboarded_merchant',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);
	register_rest_route(
		'wc/v3',
		'gla-test/enhanced-conversions',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\enable_enhanced_conversions',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\disable_enhanced_conversions',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);
}

/**
 * Set the onboarded merchant options.
 */
function set_onboarded_merchant() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::REDIRECT_TO_ONBOARDING,
		'no'
	);
	$options->update(
		OptionsInterface::MC_SETUP_COMPLETED_AT,
		1693215209
	);
	$options->update(
		OptionsInterface::GOOGLE_CONNECTED,
		true
	);
}

/**
 * Clear a previously set onboarded merchant.
 */
function clear_onboarded_merchant() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->delete( OptionsInterface::REDIRECT_TO_ONBOARDING );
	$options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
	$options->delete( OptionsInterface::GOOGLE_CONNECTED );
}


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
 * Enable enhanced conversions.
 */
function enable_enhanced_conversions() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::ADS_ENHANCED_CONVERSION_STATUS,
		'enabled'
	);
}

/**
 * Disable enhanced conversions.
 */
function disable_enhanced_conversions() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::ADS_ENHANCED_CONVERSION_STATUS,
		'disabled'
	);
}

/**
 * Check permissions for API requests.
 */
function permissions() {
	return current_user_can( 'manage_woocommerce' );
}
