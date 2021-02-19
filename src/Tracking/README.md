# Usage Tracking

_Google Listings & Ads_ implements usage tracking, based on the native [WooCommerce Usage Tracking](https://woocommerce.com/usage-tracking/), and is only enabled when WooCommerce Tracking is enabled.

When a store opts in to WooCommerce usage tracking and uses _Google Listings & Ads_, they will also be opted in to the tracking added by _Google Listings & Ads_.

## What is tracked

As in WooCommerce core, only non-sensitive data about how a store is set up and managed is tracked. We **do not track or store personal data** from your clients.

* Plugin version
* Settings
  * WordPress.com account connection status
  * Google Merchant center account connection status

<!-- TODO: add more tracking information -->

### Tracking events

All event names are prefixed by `wcadmin_gla_`.

* `extension_loaded` - DEMO when the extension is loaded

* `get_started_faq` - Clicking on getting started page faq item to collapse or expand it
  * `id`: (faq identifier)
  * `action`: (`expand`|`collapse`)

* `get_started_faq_link_clicked` - Clicking on a text link within FAQ item
  * `id`: (faq identifier)
  * `href`

* `table_header_toggle` - Toggling display of table columns
  * `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "product-feed"`)
  * `column`: name of the column
  * `status`: (`on`|`off`)

* `gla_table_sort` - Sorting table
  * `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "product-feed"`)
  * `column`: name of the column
  * `direction`: (`asc`|`desc`)

* `datepicker_update` - Changing datepicker
  * `report`: name of the report (e.g. `"dashboard" | "reports-programs" | "product-feed"`)
  * `compare, period, before, after`: Values selected in [datepicker](https://woocommerce.github.io/woocommerce-admin/#/components/packages/date-range-filter-picker/README?id=props)

* `filter` - Changing products & variations filter
  * `report`: name of the report (e.g. `"reports-products"`)
  * `filter`: value of the filter (e.g. `"all" | "single-product" | "compare-products"`)
  * `variationFilter`: value of the variation filter (e.g. `undefined | "single-variation" | "compare-variations"`)

* `tooltip_viewed` - Viewing tooltip
  * `id`: (tooltip identifier)

* `setup_mc` - Setup Merchant Center
  * `target`: button ID
  * `trigger`: action (e.g. `click`)

* `modal_open` - A modal is opend
  * `context`: indicate which modal is opened

* `modal_closed` - A modal is closed
  * `context`: indicate which modal is closed
  * `action`: indicate the modal is closed by what action (e.g. `confirm`|`dismiss` | `create-another-campaign`)
    * `confirm` is used when the confirm button on the modal is clicked
    * `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, or pressing ESC
    * `create-another-campaign` is used when the modal "Create another campaign" clicked

* `modal_content_link_click` - Clicking on a text link within the modal content
  * `context`: indicate which link is clicked
  * `href`: link's URL

<!-- -- >
## Developer Info
All new tracking info should be updated in this readme.

New snapshot data for **WC Tracker** should be hooked into `Tracking\Events\TrackerSnapshot::include_snapshot_data()`.

New **Tracks** events should be created in `Tracking\Events\Events` (extending `Tracking\Events\BaseEvent`), and need to be registered in `Tracking\Events\EventTracking::$events`. They should also be registered in the `Internal\DependencyManagement\CoreServiceProvider` class:

```php
$this->conditionally_share_with_tags( Loaded::class );
```

 /Dev Info -->
