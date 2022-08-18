#!/bin/bash

echo "Initializing WooCommerce E2E"

# This is mainly the same script as the one in WooCommerce-Admin,
# with additional tweak for GLA.

# Turn off error display temporarily. This is to prevent deprecated function
# notices from breaking the display of some screens and then E2E tests.
# Message was for WC_Admin_Notes_Deactivate_Plugin usage in core WC.
wp config set WP_DEBUG_DISPLAY false --raw
wp config set JETPACK_AUTOLOAD_DEV true --raw
wp plugin install woocommerce --activate

# Install basic auth for API requests on http.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

# GLA is automatically mapped to the docker container's plugins folder,
# we just need to activate it here.
wp plugin activate google-listings-and-ads

# GLA doesn't really need a customer account here,
# but we are leaving it intact here just in case we want to run full WooCommerce core e2e test.
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html

# Skip activation redirect, so it will not interrupt our tests.
wp transient delete _wc_activation_redirect

# Initialize pretty permalinks.
wp rewrite structure /%postname%/
