# Google Listings & Ads

A native integration with Google offering free listings and Smart Shopping ads to WooCommerce merchants.

- [WooCommerce.com product page](https://woocommerce.com/products/google-listings-and-ads/)
- [WordPress.org plugin page](https://wordpress.org/plugins/google-listings-and-ads/)
- [User documentation](https://docs.woocommerce.com/document/google-listings-and-ads/)
- [Ideas board](https://ideas.woocommerce.com/forums/133476-woocommerce?category_id=403986)

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests.

For self help, start with our [user documentation](https://docs.woocommerce.com/document/google-listings-and-ads/).

The best place to get support is the [WordPress.org Google Listings and Ads forum](https://wordpress.org/support/plugin/google-listings-and-ads/).

If you have a WooCommerce.com account, you can [start a chat or open a ticket on WooCommerce.com](https://woocommerce.com/my-account/create-a-ticket/).

## Prerequisites

 - WordPress 5.3+
 - WooCommerce 4.5+
 - PHP 7.3+

## Development

After cloning the repo, install dependencies:

 - `npm install` to install JavaScript dependencies.
 - `composer install` to gather PHP dependencies.

Now you can build the files using one of these commands:

 - `npm run build` : Build a production version
 - `npm run dev` : Build a development version
 - `npm run start` : Build a development version, watch files for changes

## Helper Scripts

There are a number of helper scripts exposed via our package.json (below list is not exhaustive, you can view the [package.json file directly](https://github.com/woocommerce/google-listings-and-ads/blob/trunk/package.json#L11) to see all):

 - `npm run lint:js` : Run eslint over the javascript files
 - `npm run lint:css` : Run stylelint over the javascript files
 - `npm run test-unit` : Run the JS test suite
 - `npm run test-unit:watch` : Run the JS test suite, watch for changes
 - `npm run test-e2e` : Run the end-to-end test suite.
 - `npm run test-e2e:watch` : Run the end-to-end test suite, watch for changes

## WordPress Code Standards

After running `composer install` to install PHP dependencies you can use the following command to run php code standards checks:

 - `./vendor/bin/phpcs`

## PHPUnit

After running `composer install` to install PHP dependencies you can use the following command run php unit tests:

 - `./vendor/bin/phpunit`

(Installation process TBC)

## E2E Testing

E2E testing uses [@wordpress/env](https://www.npmjs.com/package/@wordpress/env) which requires [Docker](https://www.docker.com/).

Make sure Docker is running in your machine, and run the following:

`npm run wp-env start` - This will automatically download and run WordPress in a Docker container. You can access it at http://localhost:8888 (development environment) and http://localhost:8888 (test environment) (Username: admin, Password: password).

`npm run test-e2e:initialize` - This will run the [initialize.sh](/tests/e2e/initialize.sh) script to initialize the WooCommerce store for the test environment.

Run E2E testing:

- `npm run test-e2e` to just run the tests one time, quick and headless.
- `npm run test-e2e:watch` to run the tests and watch for changes.
- `npm run test-e2e:watch -- --puppeteer-interactive` to run the tests, watch for changes, in interactive mode (with visible browser UI).

More info on `test-e2e` is available in [wp-scripts readme](https://github.com/WordPress/gutenberg/blob/master/packages/scripts/README.md#test-e2e) and in [Gutenberg's Testing Overview](https://github.com/WordPress/gutenberg/blob/master/docs/contributors/testing-overview.md#end-to-end-testing).

To stop the Docker container:

`npm run wp-env stop`

To delete the Docker container (this will **delete everything** in the WordPress Docker container): 

`npm run wp-env destroy`

## Docs

* [Usage Tracking](./src/Tracking/README.md)

<p align="center">
	<br/><br/>
	Made with 💜 by <a href="https://woocommerce.com/">WooCommerce</a>.<br/>
	<a href="https://woocommerce.com/careers/">We're hiring</a>! Come work with us!
</p>
