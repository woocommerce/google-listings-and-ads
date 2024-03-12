# Google Listings & Ads

[![PHP Unit Tests](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-unit-tests.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-unit-tests.yml)
[![JavaScript Unit Tests](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-unit-tests.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-unit-tests.yml)
[![PHP Coding Standards](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-coding-standards.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/php-coding-standards.yml)
[![JavaScript and CSS Linting](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-css-linting.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/js-css-linting.yml)
[![Build](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/build.yml/badge.svg)](https://github.com/woocommerce/google-listings-and-ads/actions/workflows/build.yml)

A native integration with Google offering free listings and Performance Max ads to WooCommerce merchants.

-   [Woo.com product page](https://woo.com/products/google-listings-and-ads/)
-   [WordPress.org plugin page](https://wordpress.org/plugins/google-listings-and-ads/)
-   [User documentation](https://woo.com/document/google-listings-and-ads/)
-   [Feature requests](https://woo.com/feature-requests/google-listings-and-ads/)

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests.

For self help, start with our [user documentation](https://woo.com/document/google-listings-and-ads/).

The best place to get support is the [WordPress.org Google Listings and Ads forum](https://wordpress.org/support/plugin/google-listings-and-ads/).

If you have a Woo.com account, you can [start a chat or open a ticket on Woo.com](https://woo.com/my-account/contact-support/).

## Prerequisites

We aim to support the latest two minor versions of WordPress, WooCommerce, and PHP. (L-2 policy)

-   WordPress 5.9+
-   WooCommerce 6.9+
-   PHP 7.4+ (64 bits)

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
-   `npm run start:hot` : Build a development version in Fast Refresh mode, watch files for changes.

Notice this repository has `engine-strict=true` directive set. That means you cannot install dependencies with other Node engines rather than the ones defined in the `engines` directive inside [package.json](./package.json). It's recommended to use [NVM](https://github.com/nvm-sh/nvm) and run `nvm use` before installing the dependencies to be sure you're using the recommended Node version.

We added Node `^18` and npm `^9` to allow dependabot to update our dependencies. But these are not supported versions.

## Working with DEWP

The Dependency Extraction Webpack Plugin makes working with frontend dependencies not so obvious, check [`Working with DEWP.md`](Working with DEWP.md) for more details.

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

## E2E Testing

E2E testing uses [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) which requires [Docker](https://www.docker.com/).

Make sure Docker is running in your machine, and run the following:

`npm run wp-env:up` - This will automatically download and run WordPress in a Docker container. You can access it at http://localhost:8889 (Username: admin, Password: password).

To install the PlayWright browser locally you can run:
`npx playwright install chromium`

Run E2E testing:

-   `npm run test:e2e` to run the test in headless mode.
-   `npm run test:e2e-dev` to run the tests in Chromium browser.

To remove the Docker container and images (this will **delete everything** in the WordPress Docker container):

`npm run wp-env destroy`

:warning: Currently, the E2E testing on GitHub Actions is only run automatically after opening a PR with `release/*` branches or pushing changes to `release/*` branches. To run it manually, please visit [here](../../actions/workflows/e2e-tests.yml) and follow [this instruction](https://docs.github.com/en/actions/managing-workflow-runs/manually-running-a-workflow?tool=webui) to do so.

### Test other WordPress versions
By default the latest version of WordPress will be installed. `WP_ENV_CORE` can be used to install a specific version.

```
WP_ENV_CORE=WordPress/WordPress#6.2.2 npm run wp-env:up
```

This does not work with Release Candidate versions as the tag is not available. Instead we can bring the `wp-env:up` with the latest version and then upgrade WordPress through WP CLI.

```
npm run -- wp-env run tests-cli -- wp core update --version=6.3-RC3
npm run -- wp-env run tests-cli -- wp core update-db
```

### Test other WooCommerce versions
WooCommerce is installed through WP CLI so we can use this to update to a newer version like a release candidate.

```
npm run -- wp-env run tests-cli -- wp plugin update woocommerce --version=8.0.0-rc.1
npm run -- wp-env run tests-cli -- wp wc update
```

## Docs

- [Usage Tracking](./src/Tracking/README.md)
- [Hooks defined or used in GLA](./src/Hooks/README.md)
- [gtag consent mode](./docs/gtag-consent-mode.md)

<p align="center">
	<br/><br/>
	Made with 💜 by <a href="https://woo.com/">Woo</a>.<br/>
	<a href="https://woo.com/careers/">We're hiring</a>! Come work with us!
</p>
