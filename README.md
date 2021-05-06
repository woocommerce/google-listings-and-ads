# Google Listings & Ads

A native integration with Google offering free listings and Smart Shopping ads to WooCommerce merchants.

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

## Please treat this repo as public

* Avoid posting any private or sensitive information
* Avoid posting anything that could be misunderstood by community contributors or that is heavily Automattic-centric
* Default to open and keep as much conversation in Github as possible. In cases where private conversation happens on a P2 or in Slack, post a brief summary to GitHub instead of simply adding a private link.

What is considered private?

* Personally identifiable information (PII). Usernames, emails, user IDs, blog names, blog urls, blog IDs, credit card details. It’s best not to include our own usernames, emails, user IDs, etc. Better to use test data with a test user on a test blog.
* Passwords, tokens, private keys, etc.
* Internal Automattic links. Default to summarizing the information instead. If you must it’s OK to use shorthand (e.g., 1234-wpcom) but please try to summarize.
* References to filenames in proprietary codebases. E.g. or a reference to a private repository.
* Images that show private user data… or any of the above.
