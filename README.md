# Google Listings & Ads

[![PHP Unit Tests](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-unit-tests.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-unit-tests.yml)
[![JavaScript Unit Tests](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-unit-tests.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-unit-tests.yml)
[![PHP Coding Standards](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-coding-standards.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-coding-standards.yml)
[![JavaScript and CSS Linting](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-css-linting.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-css-linting.yml)
[![E2E Tests](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/e2e-tests.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/e2e-tests.yml)
[![Bundle Size](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/bundle-size.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/bundle-size.yml)

A native integration with Google offering free listings and Smart Shopping ads to WooCommerce merchants.

-   [WooCommerce.com product page](https://woocommerce.com/products/google-listings-and-ads/)
-   [WordPress.org plugin page](https://wordpress.org/plugins/google-listings-and-ads/)
-   [User documentation](https://docs.woocommerce.com/document/google-listings-and-ads/)
-   [Ideas board](https://ideas.woocommerce.com/forums/133476-woocommerce?category_id=403986)

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests.

For self help, start with our [user documentation](https://docs.woocommerce.com/document/google-listings-and-ads/).

The best place to get support is the [WordPress.org Google Listings and Ads forum](https://wordpress.org/support/plugin/google-listings-and-ads/).

If you have a WooCommerce.com account, you can [start a chat or open a ticket on WooCommerce.com](https://woocommerce.com/my-account/create-a-ticket/).

## Prerequisites

We aim to support the latest two minor versions of WordPress, WooCommerce, and PHP. (L-2 policy)

-   WordPress 5.6+
-   WooCommerce 5.8+
-   PHP 7.3+

## Browsers supported

As per [WordPress Core Handbook](https://make.wordpress.org/core/handbook/best-practices/browser-support/) we currently support:

> -   Last 1 Android versions.
> -   Last 1 ChromeAndroid versions.
> -   Last 2 Chrome versions.
> -   Last 2 Firefox versions.
> -   Last 2 Safari versions.
> -   Last 2 iOS versions.
> -   Last 2 Edge versions.
> -   Last 2 Opera versions.
> -   Browsers with > 1% usage based on [can I use browser usage table](https://caniuse.com/usage-table)

:warning: We do not support Internet Explorer.

## Development

After cloning the repo, install dependencies:
-   `nvm use` to be sure you're using the recommended node version in `.nvmrc`
-   `npm install` to install JavaScript dependencies.
-   `composer install` to gather PHP dependencies.

Now you can build the files using one of these commands:

-   `npm run build` : Build a production version
-   `npm run dev` : Build a development version
-   `npm run start` : Build a development version, watch files for changes
-   `npm run start:hot` : Build a development version in Fast Refresh mode, watch files for changes. To use this mode, it requires
    1. The [`SCRIPT_DEBUG`](https://wordpress.org/support/article/debugging-in-wordpress/#script_debug) flag enabled.

## Helper Scripts

There are a number of helper scripts exposed via our package.json (below list is not exhaustive, you can view the [package.json file directly](https://github.com/woocommerce/google-listings-and-ads/blob/trunk/package.json#L11) to see all):

-   `npm run lint:js` : Run eslint over the javascript files
-   `npm run lint:css` : Run stylelint over the javascript files
-   `npm run test:js` : Run the JS test suite
-   `npm run test:js:watch` : Run the JS test suite, watch for changes

## WordPress Code Standards

After running `composer install` to install PHP dependencies you can use the following command to run php code standards checks:

-   `./vendor/bin/phpcs`

## PHPUnit

### Prerequisites

Install [`composer`](https://getcomposer.org/), `git`, `npm`, `svn`, and either `wget` or `curl`.

Change to the plugin root directory and type:

```bash
$ composer install
```

Change to the plugin root directory and type:

```bash
$ npm install && npm run dev
```

### Install Test Dependencies

To run the unit tests you need WordPress, [WooCommerce](https://github.com/woocommerce/woocommerce), and the WordPress Unit Test lib (included in the [core development repository](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)).

Install them using the `install-wp-tests.sh` script:

```bash
$ ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> <db-host>
```

Example:

```bash
$ ./bin/install-wp-tests.sh wordpress_tests root root localhost
```

This script installs the test dependencies into your system's temporary directory and also creates a test database.

You can also specify the path to their directories by setting the following environment variables:

-   `WP_TESTS_DIR`: WordPress Unit Test lib directory
-   `WP_CORE_DIR`: WordPress core directory
-   `WC_DIR`: WooCommerce directory

### Running Tests

Change to the plugin root directory and type:

```bash
$ vendor/bin/phpunit
```

The tests will execute and you'll be presented with a summary.

### Unit Tests and PHP 8

We currently do not support running unit tests on PHP 8..

## E2E Testing

E2E testing uses [@woocommerce/e2e-environment](https://www.npmjs.com/package/@woocommerce/e2e-environment) which requires [Docker](https://www.docker.com/).

Make sure Docker is running in your machine, and run the following:

`npm run docker:up` - This will automatically download and run WordPress in a Docker container. You can access it at http://localhost:8084 (Username: admin, Password: password).

Run E2E testing:

-   `npm run test:e2e` to run the test in headless mode.
-   `npm run test:e2e-dev` to run the tests in Chromium browser.

To remove the Docker container and images (this will **delete everything** in the WordPress Docker container):

`npm run docker:down`

## Docs

-   [Usage Tracking](./src/Tracking/README.md)

<p align="center">
	<br/><br/>
	Made with 💜 by <a href="https://woocommerce.com/">WooCommerce</a>.<br/>
	<a href="https://woocommerce.com/careers/">We're hiring</a>! Come work with us!
</p>
