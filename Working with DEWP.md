# Working with DEWP

Working `@wordpress/` and `@woocommerce/` Dependency Extraction Webpack Plugin (DEWP) could be a challenge for a developer.
See https://github.com/WordPress/gutenberg/issues/35630.

To mitigate at least some of them in this repo we try to implement a few tricks.

## Opting out

During the development process sometimes we face a need to bundle a package, that was supposed to be externalized by default. For example, because the one shipped with supported WP/WC versions has bugs, or we need a newer/older one.

This configuration is maintained in [/webpack.config.js](/develop/webpack.config.js) in the `requestToExternal` function.

## Externalized packages

Neither `@wordpress/` nor `@woocommerce/dependency-extraction-webpack-plugin` exposes an explicit list of packages to be externalized.
To know what was actually externalized, we store the report from the build time at [`.externalized.json`](.externalized.json).
So we could later inspect that.

## Selective bundling & extracting.

Sometimes the fact we do bundle a package that is provided by WordPress/WooCommerce instance introduces errors, as some packages are not effectively modular, so we face version conflicts, style collisions, etc.
Also, we'd like to reduce the size of our bundle, so eventually, we aim to extract/externalize as much as possible and when possible import from an external package.

To help with that we implemented the `extracted/` prefix. It's also a custom implementation in [/webpack.config.js](/develop/webpack.config.js).
Thanks to that even though a package is bundled, the given import would fetch it from external.

## NPM scripts

We also have a bunch of NPM scripts to help us work with them daily.

### Outdated

In a regular project to track outdated packages, you would use `npm outdated`. However, with DEWP updating a bundled package is not the same as updating externalized one. For example, if we aim to support WC L-2, we theoretically need to test our extensions with all the package versions used by that version, and we cannot update it to the latest as we go. So we have two scripts to separate those cases:

- `npm run outdated:dewp` Check for outdated packages that are listed in `.externalized.json`
- `npm run outdated:nondewp` Check for those not listed there.

### What is the version of the DEWPed package?

As we externalize packages we lose control of the version of the package that will run in the wild (on WordPress instance).
However, with the current platform, we not only lose control, but we also lose the actual information on the range of package we should expect.
So, even if we know we support WooCommerce 6.9+, what are the `@woocommerce/components` there?

There is no published table of those, and finding them usually requires quite a lot of manual digging. To mitigate that we created another script.

- `npm run dewps:woo 6.9.4` - where `6.9.4` is the version of WooCommerce you would like to check.

Please note this simple script still has several limitations.
1. It works for WooCommerce deps only. WordPress ones are more tricky to get, as the list of packages is less static and regular. Theoretically, we should be able to [use dist-tags](https://github.com/WordPress/gutenberg/issues/24376), like `npm install @wordpress/components@wp-6.1.0` or `npx wp-scripts packages-update --dist-tag=wp-5.8`.
2. It assumes all packages are prefixed with `@woocommerce/`
3. You need to provide the exact full version. The latest, or `x.y` tree lines are not being resolved automatically.
4. Some packages externalized by DEWP, are not packages we could find neither in npm nor in [`woocommerce/woocommerce/packages/js`](https://github.com/woocommerce/woocommerce/commits/trunk/packages/js/) 🤷
5. It requires at least Node 18 to run.

