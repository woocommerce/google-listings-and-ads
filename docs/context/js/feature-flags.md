# Feature Flags

GLA has no formal feature flag system. Runtime flags are boolean properties on the `glaData` global, set by PHP at page render time.

## Available Flags

| Flag | Default | Purpose |
|---|---|---|
| `glaData.enableReports` | `true` | Show/hide the Reports page and navigation |
| `glaData.serviceBasedMerchant` | `false` | Merchant uses the service-based onboarding flow |
| `glaData.mcSetupComplete` | `false` | Merchant Center account connected and configured |
| `glaData.adsSetupComplete` | `false` | Google Ads account connected |
| `glaData.mcSupportedLanguage` | `false` | Store language supported by MC |
| `glaData.mcSupportedCountry` | `false` | Store country supported by MC |

## Accessing Flags

```js
import { glaData } from '~/constants';

// Direct access — fine for simple conditions
if ( glaData.serviceBasedMerchant ) {
    // render service-based UI
}

// Wrap in a hook for testability
const useIsServiceBased = () => glaData.serviceBasedMerchant;
```

## Setting Flags (PHP Side)

Flags are set in `Admin::get_js_data()` in `src/Admin/Admin.php`:

```php
'serviceBasedMerchant' => (bool) $this->options->get( OptionsInterface::IS_SERVICE_BASED_MERCHANT ),
'mcSetupComplete'      => $this->options->get_merchant_id() > 0 && $this->mc->is_setup_complete(),
```

To add a new flag:
1. Add to the array in `Admin::get_js_data()`
2. Add to `global.glaData` in `js/src/tests/jest-unit.setup.js` with a test-appropriate default

## Testing

Flags are part of `global.glaData` in `jest-unit.setup.js`. Override per test:

```js
describe( 'service-based flow', () => {
    beforeEach( () => {
        global.glaData.serviceBasedMerchant = true;
    } );

    afterEach( () => {
        global.glaData.serviceBasedMerchant = false;
    } );

    it( 'shows service merchant UI', () => {
        render( <MyComponent /> );
        expect( screen.getByText( 'Service Plan' ) ).toBeInTheDocument();
    } );
} );
```

## Static Nature

Flags are snapshot values from PHP render time. They do not react to store changes or API responses during the session. If a flag must change mid-session (rare), trigger a full page reload after the underlying data changes.

## Conditional Pages

`serviceBasedMerchant` gates entire pages — the `Onboarding` page vs. the `SetupMC` page, for example. The conditional rendering happens in `js/src/index.js` during page registration.
