# Google for WooCommerce

A native integration with Google that will allow merchants to easily display their products across Google’s network.

## Prerequisites

WordPress 5.3+ and WooCommerce 4.0+

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
