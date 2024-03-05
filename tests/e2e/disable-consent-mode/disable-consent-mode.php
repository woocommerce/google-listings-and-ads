<?php
/**
 * Plugin name: Custom Consent Mode
 * Description: A plugin to customize/disable the gtag consent mode, to make testing esier by granting everything by default.
 *
 * Intended to function as a plugin while tests are running.
 * It hopefully goes without saying, this should not be used in a production environment.
 * It's a hack to avoid specifying region for E2E environment, but it tests the customization of consent mode.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Snippets;

add_filter(
    'woocommerce_gla_gtag_consent',
    function( $old_config ) {
        return "gtag( 'consent', 'default', {
            analytics_storage: 'granted',
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'granted',
        } );
        ";
    }
);
