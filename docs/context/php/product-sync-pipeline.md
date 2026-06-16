# Product Sync Pipeline

GLA syncs WooCommerce products to Google Merchant Center through an event-driven async pipeline.

## Pipeline Overview

```
WC product save/delete event
    ↓ SyncerHooks (WordPress hooks)
    ↓ deduplication check ($already_scheduled)
    ↓ JobRepository::create_product_update_job( [product_ids] )
         or create_product_delete_job( [product_ids] )
    ↓ ActionScheduler (async)
    ↓ UpdateProducts or DeleteProducts job
    ↓ ProductSyncer::sync_products( $wc_products )
    ↓ WCProductAdapter (WC_Product → Google Product)
    ↓ Symfony Validator (validates Google product schema)
    ↓ ProductSyncer → GoogleProductService
    ↓ Google Merchant Center API (batched)
```

## Key Classes

### SyncerHooks (`src/Product/SyncerHooks.php`)

Registers WordPress/WC product hooks and dispatches sync jobs:

```php
// Hooks listened to:
add_action( 'save_post_product', ... );          // simple + variable products
add_action( 'delete_post', ... );                // product deletion
add_action( 'update_post_meta', ... );           // variant updates
add_action( 'woocommerce_new_product_variation', ... );
```

Deduplication: tracks `$already_scheduled[]` to avoid scheduling the same product multiple times per request.

### WCProductAdapter (`src/Product/WCProductAdapter.php`)

Converts `WC_Product` to Google's product schema:

```php
// Extends Google's Product class with WooCommerce data mapping
class WCProductAdapter extends GoogleProduct implements Validatable {
    // Simple products: $this->wc_product
    // Variations: both $this->wc_product (variation) and $this->parent_wc_product
}
```

- Applies attribute mapping rules via `AttributeMappingHelper`
- Available extension point: `woocommerce_gla_product_attribute_values` filter
- Never instantiate directly — goes through `ProductHelper::generate_adapted_product()`

### ProductFilter / ProductRepository (`src/Product/`)

Gate which products reach the syncer:

- `ProductFilter::is_syncable()` — product must be published, have a price, pass visibility checks
- `ProductRepository` — queries WC products meeting sync criteria (handles HPOS compatibility)
- `ProductMetaHandler` — reads/writes per-product GLA metadata (sync status, errors, last sync time)

### Coupon Sync

Mirrors the product sync pattern under `src/Coupon/`:
- `SyncerHooks` → `WCCouponAdapter` → `CouponSyncer` → Google Merchant Center Promotions API

## Adding Product Data to Sync

To add a new field to the Google product payload:

1. Add a setter/getter to `WCProductAdapter` mapping a WC product attribute to a Google Product field
2. Hook into `woocommerce_gla_product_attribute_values` filter if the value comes from custom meta

To add a new attribute mapping rule:

1. Add a rule class under `src/Product/AttributeMapping/`
2. Register it in `AttributeMappingHelper`

## Error Handling

- Symfony Validator runs against the adapted product before the API call
- Invalid products are tracked via `ProductMetaHandler::update_failed_sync_attempts()`
- Products with persistent failures are marked with `ProductSyncStats::SYNC_FAILED` status
