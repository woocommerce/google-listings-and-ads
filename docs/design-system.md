# WordPress Design System tooling

Google for WooCommerce lints and builds its UI code against the [WordPress Design System](https://wordpress.github.io/gutenberg/?path=/docs/design-system-introduction--docs) (WPDS): the components in `@wordpress/ui` and the `--wpds-*` design tokens in `@wordpress/theme`.

## Components: `wpds/use-recommended-components`

The ESLint rule [`use-recommended-components`](https://github.com/WordPress/gutenberg/blob/trunk/packages/eslint-plugin/docs/rules/use-recommended-components.md) flags imports of components that WordPress no longer recommends, such as `Card`, `Flex` or `Notice` from `@wordpress/components`, and names the replacement. It also flags `@wordpress/ui` components that are not yet marked as ready to use.

- The rule runs as `wpds/use-recommended-components`. `@woocommerce/eslint-plugin` registers an older copy of `@wordpress/eslint-plugin` under the `@wordpress` name, so `eslint.config.js` takes this rule from the latest copy and registers it under its own name.
- Existing uses are recorded in `eslint-suppressions.json` ([ESLint bulk suppressions](https://eslint.org/docs/latest/use/suppressions)). Only new uses fail.
- After you migrate a file, run `npm run lint:js:prune-suppressions` and commit the updated `eslint-suppressions.json`. Lint fails while the file still lists uses that no longer exist.
- Do not add new entries to the suppressions file to get a change through. Use the recommended component, or discuss the exception in the pull request.

## Design tokens

`.stylelintrc.json` extends `@wordpress/stylelint-config`, which turns on three token rules for every stylesheet:

- `plugin-wpds/no-unknown-ds-tokens`: every `--wpds-*` reference must be a real token.
- `plugin-wpds/no-token-fallback-values`: write `var(--wpds-dimension-gap-lg)`, not `var(--wpds-dimension-gap-lg, 16px)`.
- `plugin-wpds/no-setting-wpds-custom-properties`: do not define or override `--wpds-*` properties.

The fallback values are added at build time instead. `webpack.config.js` adds `postcss-ds-token-fallbacks` from `@wordpress/theme-build-tools` to the PostCSS step, so `var(--wpds-dimension-gap-lg)` ships as `var(--wpds-dimension-gap-lg, 16px)`.

## Versions

Update these development dependencies together, so the lint rules, the token list and the fallback values all describe the same release of the design system:

- `@wordpress/eslint-plugin`
- `@wordpress/stylelint-config`
- `@wordpress/theme-build-tools`
- `@wordpress/theme`

After an update, run `npm run lint:js`, `npm run lint:css` and `npm run lint:js:prune-suppressions`.
