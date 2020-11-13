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

All event names are prefixed by `wcadmin_woogle_`.

* `extension_loaded` - DEMO when the extension is loaded

<!-- -- >
## Developer Info
All new tracking info should be updated in this readme.

New snapshot data for **WC Tracker** should be hooked into `Tracking\Events\TrackerSnapshot::include_snapshot_data()`.

New **Tracks** events should be created in `Tracking\Events\Events` (implementing `Tracking\Events\TracksEventInterface`, using `Tracking\Events\EventHelper`), and need to be registered in `Tracking\Events\EventTracking::$events`.

 /Dev Info -->
