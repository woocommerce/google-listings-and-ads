# Concepts

Shared domain vocabulary for this project — entities, named processes, and status concepts with project-specific meaning. Seeded with core domain vocabulary, then accretes as ce-compound and ce-compound-refresh process learnings; direct edits are fine. Glossary only, not a spec or catch-all.

## Product sync

### Channel visibility
A merchant's per-product choice of whether the product is sent to Google Merchant Center at all: sync and show, or don't sync and show.

It is stored on the parent product. A variation follows its parent's choice, so a variation usually has no visibility value of its own. A product with no value is treated as sync and show.

### Sync-ready product
A product the plugin is allowed to send to Merchant Center right now. It must be a supported product type, published, visible in the catalog, and not set to don't sync and show.

A variation is judged by its parent's published status rather than its own. A disabled variation can therefore still be sync-ready when the store chooses to show disabled variations.

### Synced product
A product that Merchant Center has accepted at least once. The plugin keeps the time of its last successful sync and the Merchant Center IDs it was given.

### Expiring product
A synced product whose last successful sync is older than the resubmission window. Merchant Center listings lapse if they are not refreshed periodically, so a recurring background job resubmits expiring products in batches. That job keeps unchanged products listed, because routine syncs skip a product that has not changed until it reaches the resubmission window.
