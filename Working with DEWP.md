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

As we externalize a package, we lose control of its version that will run in the wild (on WordPress instance).
However, with the current platform, we not only lose control, but we also lose the actual information on the range of package versions we should expect.
So, even if we know we support WooCommerce L-2, what are the `@woocommerce/components` there?

There is no published table of those, and finding them usually requires quite a lot of manual digging. To mitigate that, we created a tool: [`dewped`](https://github.com/woocommerce/dewped#dewped).

First, `latest-versions`/`l-x` helps you check the platform's current, latest, or L-x version:

```bash
$ dewped l-x
Fetching L-2 versions wordpress!
["6.3.1","6.2.2","6.1.3"]

$ dewped l-x woocommerce 3
Fetching L-3 versions woocommerce!
["8.0.3","7.9.0","7.8.2","7.7.2"]
```

Then, with `platform-dependency-version`/`pdep` you may check which version of packages is expected to be present in the platform you target to support and compare it to the locally installed versions.

```bash
$ dewped pdep -w=6.2.2 -c=7.8.2 -d=.externalized.json
 Name                               WordPress 6.2.2 WooCommerce 7.8.2 Local    
 ────────────────────────────────── ─────────────── ───────────────── ──────── 
 @woocommerce/components                            12.0.0            ^10.3.0  
…
```


You can also use it to check an individual package. For example, when you consider adding a new dependency and want to check which version to anticipate

```bash
$ dewped pdep --wcVersion=7.8.2 @woocommerce/data 
 Name              WooCommerce 7.8.2 Local  
 ───────────────── ───────────────── ────── 
 @woocommerce/data 4.1.0             ^4.1.0 
```

Please bear in mind there are still dragons:
1. :warning: By the design of DEWP, there is absolutely no guarantee that the package will be there at the version reported by this tool. DEWP makes use of global variables available in runtime. So, any other extension or custom code running in a particular instance can overwrite what you expect in a package.
2. The fact that `dewped pdep package` reports a version of a package does not mean it was actually externalized from your bundle. It only means WP/WC uses a reported version. To check what was effectively externalized, please inspect your Webpack config and DEWP report file (`externalizedReport`).
3. Some packages externalized by DEWP, like `@woocommerce/settings`, are not packages we could find either in npm or in [`woocommerce/woocommerce/packages/js`](https://github.com/woocommerce/woocommerce/commits/trunk/packages/js/) 🤷. There may be no way for you to install them locally or even reason about their versions.
4. The `dewped` tool implementation relies on the internal structure of WordPress and WooCommerce repos, which is not documented, considered API, or even stable. So, it may potentially change at any time, making this tool fail or return invalid results 🤷.

(If any of the above caveats bothers you or makes you even even more confused, please refer to https://github.com/WordPress/gutenberg/issues/35630)


