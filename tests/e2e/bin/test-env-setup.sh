#!/usr/bin/env bash

echo -e 'Activate twentytwentytwo theme \n'
wp-env run tests-cli wp theme activate twentytwentytwo

echo -e 'Install WooCommerce \n'
wp-env run tests-cli -- wp plugin install woocommerce --activate

echo -e 'Activate Google Listings and Ads \n'
wp-env run tests-cli -- wp plugin activate google-listings-and-ads

echo -e 'Update URL structure \n'
wp-env run tests-cli -- wp rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
wp-env run tests-cli wp user create customer customer@woocommercee2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'

echo -e 'Update Blog Name \n'
wp-env run tests-cli wp option update blogname 'WooCommerce E2E Test Suite'

echo -e 'Adding basic WooCommerce settings... \n'
wp-env run tests-cli wp wc payment_gateway update cod --enabled=1 --user=admin

echo -e 'Set the tour of product block editor to not display \n'
wp-env run tests-cli wp option update woocommerce_block_product_tour_shown 'yes'

echo -e 'Set the variable product tour of classic product editor to not display \n'
wp-env run tests-cli wp user meta update admin woocommerce_admin_variable_product_tour_shown '"yes"'
