# Build System

GLA uses webpack with multiple entry points, outputting to `js/build/`.

## Entry Points

| Entry | Output Handle | Loaded On |
|---|---|---|
| `js/src/index.js` | `gla-wp-admin-index` | All GLA admin pages |
| `js/src/blocks/index.js` | `gla-blocks` | Block editor |
| `js/src/product-attributes/index.js` | `gla-product-attributes` | Product edit screen |
| `js/src/gtag-events/index.js` | `gla-gtag-events` | All frontend pages |
| `js/src/notification-manager/index.js` | `gla-notification-manager` | Admin pages |
| `js/src/shims/wp-dataviews.js` | `gla-wp-dataviews-shim` | Before `index.js` |
| `js/src/meta-boxes/order-attribution/index.js` | `gla-order-attribution` | Order edit screen |
| `js/src/meta-boxes/channel-visibility/index.js` | `gla-channel-visibility-meta-box` | Product edit screen |

## Path Alias

`~` → `js/src/` in both webpack and jest:

```js
import { glaData } from '~/constants';  // resolves to js/src/constants.js
```

No `~` alias in SCSS files.

## Externalized Dependencies

`@wordpress/*` and `@woocommerce/*` packages are excluded from the bundle via the Dependency Extraction Webpack Plugin. They are loaded by the host WordPress environment. Full list in `.externalized.json`.

```js
// These are externalized — do NOT add as bundled deps in package.json
import { useSelect } from '@wordpress/data';
import { Button } from '@woocommerce/components';
```

**Exception:** `@wordpress/dataviews` is bundled (not externalized). It is loaded via the `gla-wp-dataviews-shim` entry point before the main bundle, and the module is available at `glaData.dataViewsScriptUrl`.

## Built Dependency Array

Each entry point also outputs `js/build/{name}.asset.php` — a PHP file containing:
- `dependencies` — array of script handles this bundle requires
- `version` — content hash for cache busting

`AdminScriptWithBuiltDependenciesAsset` reads this file automatically. Do not specify dependencies manually for webpack-built assets.

## SCSS

Global abstracts are auto-imported by `sass-loader` — no manual `@import` needed:

```scss
/* _colors, _variables, _mixins, _breakpoints are already available */
.gla-my-component {
    color: $color-primary;
    @include breakpoint-mobile { ... }
}
```

Abstracts live in `js/src/css/abstracts/`.

## Code Splitting

Pages are lazy-loaded via dynamic `import()` with a required `webpackChunkName` comment:

```js
const Dashboard = lazy( () =>
    import( /* webpackChunkName: "dashboard" */ './pages/dashboard' )
);
```

The `commons` chunk bundles shared code from `components/`, `data/`, `hooks/`, `utils/`, and `images/`.

## Build Commands

```bash
npm run build       # production (minified)
npm run dev         # development (no minification)
npm run start       # watch mode
npm run start:hot   # watch with React Fast Refresh (HMR)
```
