# Google API Clients

GLA communicates with Google Ads and Merchant Center via PHP wrapper classes layered over vendor-namespaced Google client libraries.

## Namespace

All Google client library classes are vendor-prefixed to avoid conflicts:

```php
// Wrong — bare Google namespace
use Google\Ads\GoogleAds\V18\Services\GoogleAdsServiceClient;

// Correct — vendor-namespaced
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Ads\GoogleAds\V18\Services\GoogleAdsServiceClient;
```

After `composer install`, `bin/prefix-vendor-namespace.php` rewrites all vendor namespaces. Never import from vendor classes without the `Automattic\WooCommerce\GoogleListingsAndAds\Vendor\` prefix.

## Wrapper Layer (`src/API/Google/`)

Thin PHP wrappers over the vendor clients:

| Class | Purpose |
|---|---|
| `Ads` | Google Ads account info |
| `AdsCampaign` | Campaign CRUD |
| `AdsConversionAction` | Conversion action management |
| `AdsAssetGroup` | Performance Max asset groups |
| `Merchant` | Merchant Center account |
| `MerchantReport` | Merchant Center reporting |
| `MerchantIssues` | Account/product issues |

These wrappers handle:
- Translating plugin domain objects to/from protobuf messages
- Pagination via `PagedListResponse`
- API error code mapping

## Authentication

`src/Google/Middleware.php` injects WPCom/Jetpack OAuth tokens into every outbound request:

```php
// Automatically applied — do not re-implement auth
// The token comes from the WPCom proxy, not stored directly in GLA
```

## Client Factory

`src/Google/Ads/` contains traits that construct low-level GAPIC clients:

```php
// GoogleAdsClientTrait builds the GoogleAdsClient with auth + endpoint config
// Used by domain services, not called directly from controllers
```

## Domain Service Layer

Controllers and jobs call domain services, not the API wrappers directly:

```
Controller/Job → AdsService → Ads (API wrapper) → Google Ads API
Controller/Job → MerchantCenterService → Merchant (API wrapper) → Merchant Center API
```

Never call `src/API/Google/` classes from a controller. Always go through a domain service.

## Error Handling

`ExceptionTrait` wraps Google API exceptions into GLA-specific exceptions:

```php
// Google throws ApiException → ExceptionTrait converts to:
ApiNotReachableException    // network/auth failures
ApiInvalidTokenException    // expired OAuth token
InvalidValue                // invalid input
```

Catch `ExceptionTrait`-wrapped exceptions in callers; let `ResponseFromExceptionTrait` in controllers convert them to REST error responses.

## Batching

Merchant Center and Ads API calls are batched for efficiency:
- `BatchProductHelper` groups products for MC batch insert/update/delete
- Ads operations use Google Ads API's native `MutateOperation` batch support
