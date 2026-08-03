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
		'gla-test/ads-completed',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_ads_completed_at',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_ads_completed_at',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);
	register_rest_route(
		'wc/v3',
		'gla-test/mc-completed',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_mc_completed_at',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_mc_completed_at',
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

	register_rest_route(
		'wc/v3',
		'gla-test/gtin-disabled',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_disabled_gtin_version',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);

	register_rest_route(
		'wc/v3',
		'gla-test/gtin-hidden',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_hidden_gtin_version',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
		],
	);

	register_rest_route(
		'wc/v3',
		'gla-test/service-based-merchant',
		[
			[
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\set_service_based_merchant',
				'permission_callback' => __NAMESPACE__ . '\permissions',
			],
			[
				'methods'             => 'DELETE',
				'callback'            => __NAMESPACE__ . '\clear_service_based_merchant',
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
	$options->update(
		OptionsInterface::ONBOARDING_COMPLETED_AT,
		1693215209
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
	$options->delete( OptionsInterface::ONBOARDING_COMPLETED_AT );
}

/**
 * Set the service based merchant option.
 */
function set_service_based_merchant() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::IS_SERVICE_BASED_MERCHANT,
		'yes'
	);
}

/**
 * Clear a previously set service based merchant option.
 */
function clear_service_based_merchant() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::IS_SERVICE_BASED_MERCHANT,
		'no'
	);
}

/**
 * Set the ADS_SETUP_COMPLETED_AT option.
 */
function set_ads_completed_at() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::ADS_SETUP_COMPLETED_AT,
		1693215209
	);
}

/**
 * Clear a previously set ADS_SETUP_COMPLETED_AT option.
 */
function clear_ads_completed_at() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->delete( OptionsInterface::ADS_SETUP_COMPLETED_AT );
}

/**
 * Set the MC_SETUP_COMPLETED_AT option.
 */
function set_mc_completed_at() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update(
		OptionsInterface::MC_SETUP_COMPLETED_AT,
		1693215209
	);
}

/**
 * Clear a previously set MC_SETUP_COMPLETED_AT option.
 */
function clear_mc_completed_at() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->delete( OptionsInterface::MC_SETUP_COMPLETED_AT );
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

/**
 * Set gla_install_version as 2.9.0 for hiding the GTIN
 * Notice GTIN should be hidden when gla_install_version > 2.8.7
 */
function set_hidden_gtin_version() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update( OptionsInterface::INSTALL_VERSION, '2.9.0' );
}

/**
 * Set gla_install_version for set the GTIN as readonly
 * Notice GTIN should be visible but disabled when gla_install_version <= 2.8.7
 */
function set_disabled_gtin_version() {
	/** @var OptionsInterface $options */
	$options = woogle_get_container()->get( OptionsInterface::class );
	$options->update( OptionsInterface::INSTALL_VERSION, '2.8.7' );
}
