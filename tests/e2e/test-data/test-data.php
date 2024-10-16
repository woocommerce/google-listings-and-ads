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
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;

add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );
add_filter( 'woocommerce_gla_notify', '__return_false'); // avoid any request to google in the tests

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
		'gla-test/notifications-ready',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_notifications_ready',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_notifications_ready',
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
 * Check permissions for API requests.
 */
function permissions() {
	return current_user_can( 'manage_woocommerce' );
}

/**
 * Set the Notifications Service as ready.
 */
function set_notifications_ready() {
	/** @var OptionsInterface $options */
	$options    = woogle_get_container()->get( OptionsInterface::class );
	$transients = woogle_get_container()->get( TransientsInterface::class );
	$transients->set( TransientsInterface::URL_MATCHES, 'yes' );
	$transients->set(
		TransientsInterface::WPCOM_API_STATUS,
		array(
			'is_healthy'               => true,
			'is_wc_rest_api_healthy'   => true,
			'is_partner_token_healthy' => true
		)
	);
	$options->update(
		OptionsInterface::WPCOM_REST_API_STATUS, 'approved'
	);
}
/**
 * Clear the Notifications Service.
 */
function clear_notifications_ready() {
	/** @var OptionsInterface $options */
	$options    = woogle_get_container()->get( OptionsInterface::class );
	$transients = woogle_get_container()->get( TransientsInterface::class );
	$transients->delete( TransientsInterface::URL_MATCHES );
	$options->delete( OptionsInterface::WPCOM_REST_API_STATUS );
}

add_filter( 'woocommerce_gla_ads_billing_setup_status', function( $status ) {
	return 'approved';
} );
