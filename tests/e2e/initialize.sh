#!/bin/bash

echo "Adding basic WooCommerce settings..."
npm run wp-env run tests-cli 'wp option set woocommerce_store_address "60 29th Street"'
npm run wp-env run tests-cli 'wp option set woocommerce_store_address_2 "#343"'
npm run wp-env run tests-cli 'wp option set woocommerce_store_city "San Francisco"'
npm run wp-env run tests-cli 'wp option set woocommerce_default_country "US:CA"'
npm run wp-env run tests-cli 'wp option set woocommerce_store_postcode "94110"'
npm run wp-env run tests-cli 'wp option set woocommerce_currency "USD"'
npm run wp-env run tests-cli 'wp option set woocommerce_product_type "both"'

echo "Importing WooCommerce shop pages..."
npm run wp-env run tests-cli "wp wc --user=admin tool run install_pages"

echo "Installing and activating the WordPress Importer plugin"
npm run wp-env run tests-cli "wp plugin install wordpress-importer --activate"
echo "Importing the WooCommerce sample data..."
npm run wp-env run tests-cli "wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip"

# # This plugin is required for WooCommerce e2e-utils to work
npm run wp-env run tests-cli "wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate"

# Create the page which is used to determine if the test environment has been setup
npm run wp-env run tests-cli "wp post create --post_type=page --post_status=publish --post_title='Ready' --post_content='E2E-tests.'"

echo "Success! E2E test environment has been setup"
