#!/usr/bin/env bash

echo -e 'Activate twentytwentytwo theme \n'
wp-env run tests-cli wp theme activate twentytwentytwo

echo -e 'Update URL structure \n'
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
wp-env run tests-cli wp user create customer customer@woocommercee2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'

echo -e 'Update Blog Name \n'
wp-env run tests-cli wp option update blogname 'WooCommerce E2E Test Suite'

echo -e 'Create Ready Post \n'
wp-env run tests-cli -- wp post create --post_type=page --post_status=publish --post_title='Ready'
