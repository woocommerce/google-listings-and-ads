# PHP–JS Bridge

PHP passes runtime configuration to JavaScript via inline script data. The primary bridge is the `glaData` global object.

## glaData

Set by `Admin.php` via `add_inline_script()` on the `gla-wp-admin-index` script handle.

**Access in JS:**

```js
import { glaData } from '~/constants';
// glaData is re-exported from window.glaData
```

**Available properties:**

| Property | Type | Description |
|---|---|---|
| `slug` | string | Plugin slug (`'gla'`) |
| `mcSetupComplete` | boolean | Merchant Center connected and configured |
| `adsSetupComplete` | boolean | Google Ads account connected |
| `mcSupportedLanguage` | boolean | Store language supported by MC |
| `mcSupportedCountry` | boolean | Store country supported by MC |
| `enableReports` | boolean | Reports feature enabled |
| `serviceBasedMerchant` | boolean | Merchant uses service-based flow |
| `dateFormat` | string | WordPress `date_format` option |
| `timeFormat` | string | WordPress `time_format` option |
| `siteLogoUrl` | string | Site logo URL (if set) |
| `dataViewsScriptUrl` | string | URL for the bundled dataviews shim |
| `initialWpData.version` | string | Plugin version |
| `initialWpData.mcId` | number | Merchant Center ID (0 if not connected) |
| `initialWpData.adsId` | number | Google Ads ID (0 if not connected) |

## Store Hydration

`initialWpData` pre-populates the `wc/gla` store so components have data immediately:

```js
// In js/src/data/index.js
dispatch( STORE_KEY ).hydratePrefetchedData( glaData.initialWpData );
```

## Adding a New Property

1. Add to `Admin::get_js_data()` in `src/Admin/Admin.php`:
   ```php
   'myNewFlag' => (bool) $this->options->get( OptionsInterface::MY_FLAG ),
   ```

2. Update the jest setup so tests have the property:
   ```js
   // js/src/tests/jest-unit.setup.js
   global.glaData = {
       // ... existing ...
       myNewFlag: false,  // add here with a test-appropriate default
   };
   ```

   Omitting this step will cause tests that import `glaData` to receive `undefined` for the new property.

## glaProductData

A separate inline script for the product editing screen:

```js
// Only available on product edit pages
import { glaProductData } from '~/constants';
```

Set by `ProductAssets` class — separate from `glaData`.

## Flags Are Static

`glaData` is set at PHP render time and does not update reactively. If a flag changes (e.g., MC connects during setup), a full page reload is required. Do not expect `glaData` values to reflect changes made during the current page session.
