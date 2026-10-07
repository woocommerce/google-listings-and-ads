#!/usr/bin/env bash

# All setup commands run in a single `wp-env run` call, since each call
# spawns a new container exec and adds a few seconds of overhead.
read -r -d '' SETUP <<'SETUP'
echo -e 'Activate twentytwentytwo theme \n'
wp theme activate twentytwentytwo

echo -e 'Install WooCommerce \n'
wp plugin install woocommerce --activate

echo -e 'Install WP Consent API \n'
wp plugin install wp-consent-api --activate

echo -e 'Activate Google for WooCommerce \n'
wp plugin activate google-listings-and-ads

echo -e 'Update URL structure \n'
wp rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
wp user create customer customer@woocommercee2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'

echo -e 'Update Blog Name \n'
wp option update blogname 'WooCommerce E2E Test Suite'

echo -e 'Adding basic WooCommerce settings... \n'
wp wc payment_gateway update cod --enabled=1 --user=admin

echo -e 'Set the tour of product block editor to not display \n'
wp option update woocommerce_block_product_tour_shown 'yes'

echo -e 'Set the variable product tour of classic product editor to not display \n'
wp user meta update admin woocommerce_admin_variable_product_tour_shown '"yes"'

echo -e 'Set the store as live \n'
wp option update woocommerce_coming_soon 'no'
SETUP

wp-env run tests-cli -- bash -c "$SETUP"
