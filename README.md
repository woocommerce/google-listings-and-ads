# Google Listings & Ads

A native integration with Google offering free listings and Smart Shopping ads to WooCommerce merchants.

## Prerequisites (TBC)

 - WordPress 5.3+
 - WooCommerce 4.5+
 - PHP 7.0+

Some considerations...
 - PHP 7.2 is recommended but [Woo still supports 7.0](https://docs.woocommerce.com/document/update-php-wordpress/)
 - We're considering using new 7.1+ syntax
 - We're considering using dependency injection containers (building off those available in WooCommerce 4.7)
 - We're building using more modern React UI elements (WP 5.3) which was only bumped to be the minimum for WC 4.5
 - Note - full Launch is estimated to not be until April 5, 2021

## Development

After cloning the repo, install dependencies:

 - `npm install` to install JavaScript dependencies.
 - `composer install` to gather PHP dependencies.

Now you can build the files using one of these commands:

 - `npm run build` : Build a production version
 - `npm run dev` : Build a development version
 - `npm run start` : Build a development version, watch files for changes

## Helper Scripts

There are a number of helper scripts exposed via our package.json (below list is not exhaustive, you can view the [package.json file directly](https://github.com/woocommerce/google-for-woocommerce/blob/trunk/package.json#L11) to see all):

 - `npm run lint:js` : Run eslint over the javascript files
 - `npm run lint:css` : Run stylelint over the javascript files
 - `npm run test:unit` : Run the JS test suite
 - `npm run test:unit:watch` : Run the JS test suite, watch for changes

## WordPress Code Standards

After running `composer install` to install PHP dependencies you can use the following command to run php code standards checks:

 - `./vendor/bin/phpcs`

## PHPUnit

After running `composer install` to install PHP dependencies you can use the following command run php unit tests:

 - `./vendor/bin/phpunit`

(Installation process TBC)

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
