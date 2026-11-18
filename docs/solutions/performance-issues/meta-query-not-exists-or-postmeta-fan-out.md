---
title: NOT EXISTS plus OR in a WP_Meta_Query fans out over postmeta and can stall product queries
date: 2026-11-18
category: performance-issues
module: Product sync (ProductRepository)
problem_type: performance_issue
component: database
symptoms:
  - "The resubmit_expiring_products create_batch action stays in progress for hours on large catalogs"
  - "MariaDB shows the product query in the Sending data state until it is killed or times out"
  - Most batches finish in about a second, but the batch that reaches a product with many meta rows never finishes
  - Overlapping create_batch actions and slow wp-admin while the query runs
root_cause: wrong_api
resolution_type: code_fix
severity: high
framework_version: "wordpress 7.1.2, mariadb 10.11"
tags: [wp-meta-query, postmeta, not-exists, left-join, fan-out, query-plan, expiring-products, keyset-pagination]
---

# NOT EXISTS plus OR in a WP_Meta_Query fans out over postmeta and can stall product queries

## Problem

`ProductRepository::find_expiring_product_ids()` feeds the daily resubmit job, which keeps unchanged products from expiring in Merchant Center. It built one `meta_query` from three parts: visibility (`NOT EXISTS` OR `!= dont-sync-and-show`), errors (`NOT EXISTS` OR `= ''`) and `synced_at < threshold`. On large catalogs a single 100-ID batch could run for hours, and the job stopped refreshing products.

## Symptoms

- The `gla/jobs/resubmit_expiring_products/create_batch` action stays in progress for many hours. Action Scheduler times it out and queues the same cursor again, so runs overlap.
- The query sits in MariaDB's `Sending data` state. Other batches of the same job take about a second.
- It only happens on some stores, and on the same store only for some batches.

## What Didn't Work

- **Benchmarking a fixture where most products had visibility and errors rows.** The old query took 0.06 to 0.6 s per batch there (15 s for a full run), so the stall seemed not to reproduce. With those rows present, MariaDB drives the join from the `synced_at` alias in ID order and stops at the LIMIT, which hides the fan-out.
- **Timing one heavy product on its own.** Copying a single product with about 600 meta rows into an empty database ran in 0.15 s. On tiny tables the optimizer picks a cheap plan, so the stall only shows up with full-size table statistics.
- **Adding `meta_key` to the `ON` clauses** (considered and rejected). While any clause uses `NOT EXISTS`, every join stays a `LEFT JOIN`. The `OR ... IS NULL` branches still keep every meta row.

## Solution

Keep only the `synced_at` clause in `meta_query`. That gives a single postmeta join and one matching row per product. Express the visibility and errors conditions as `NOT EXISTS` lookups keyed on `post_id` and `meta_key` inside the scoped `posts_where` filter the method already used for its cursor (`src/Product/ProductRepository.php:330`, filter added and removed at `:369`/`:373`):

```php
// Before: three clause groups, five postmeta joins (three on post ID only).
'meta_query' => [
    'relation' => 'AND',
    $this->get_sync_ready_products_meta_query(), // visibility NOT EXISTS OR !=
    $this->get_valid_products_meta_query(),      // errors NOT EXISTS OR = ''
    [ 'key' => ProductMetaHandler::KEY_SYNCED_AT, 'compare' => '<', 'value' => $threshold ],
],

// After: one join in meta_query; the rest as keyed subqueries in posts_where.
'meta_query' => [
    [ 'key' => ProductMetaHandler::KEY_SYNCED_AT, 'compare' => '<', 'value' => $threshold ],
],
// posts_where adds:
//   AND posts.ID > %d
//   AND ( posts.post_status = 'publish' OR posts.post_type = 'product_variation' )
//   AND NOT EXISTS ( SELECT 1 FROM postmeta WHERE post_id = posts.ID AND meta_key = <visibility> AND meta_value = 'dont-sync-and-show' )
//   AND NOT EXISTS ( SELECT 1 FROM postmeta WHERE post_id = posts.ID AND meta_key = <errors> AND meta_value <> '' )
```

The query still goes through `wc_get_products()`, so the `woocommerce_gla_product_query_args` filter, the WPML all-languages wrapper and per-site table prefixes keep working. Variations are left out of the publish check because `ProductHelper::is_sync_ready()` judges a variation by its parent's status.

Measured on MariaDB 10.11 fixtures with 18,000 products and variations and 541k to 597k postmeta rows, at the default batch size of 100:

| Fixture | Old query | New query |
|---|---|---|
| Visibility and errors rows on most products | slowest batch 0.58 s | slowest batch 0.06 s |
| Rows rare; 20 products with ~1,500 meta rows | batch 6 exceeded a 900 s cap | slowest batch 0.048 s |
| GLA's real layout (visibility on parents only, errors 5%) | batch 3 exceeded a 300 s cap | slowest batch 0.052 s |

## Why This Works

Two pieces of WordPress core behavior combine into the fan-out:

- A `NOT EXISTS` clause emits a `LEFT JOIN` with `meta_key` in the `ON` clause. Every other compare emits an `INNER JOIN ... ON ( ID = post_id )` only, with the key test in `WHERE` (`wp-includes/class-wp-meta-query.php:596-611`, WordPress 7.1.2).
- If any join is a `LEFT JOIN`, all of them become `LEFT JOIN`s (`class-wp-meta-query.php:376-378`).

The old query had three post-ID-only aliases: visibility `!=`, errors `= ''` and `synced_at <`. When a product has no visibility row (or no errors row), the `IS NULL` side of that OR is true. Every one of the product's M meta rows then survives that group's alias. With both rows missing, that gives T × M × M joined rows per product, where T is its term rows from WooCommerce's type `tax_query`. The `synced_at` alias filters its key in `WHERE`, so where the optimizer joins it decides whether that blows up:

- If the optimizer joins the `synced_at` alias first, it filters early and the cost stays small.
- On realistic statistics MariaDB reads posts first and joins `synced_at` last. Each surviving row then re-reads all M meta rows, about T × M³ reads per product. One product with 1,500 meta rows is enough to stall a batch.

GLA's own meta layout makes the expensive case common:

- Visibility is written only on the parent product (`ProductHelper::update_empty_visibility()`, `src/Product/ProductHelper.php:317`, which calls `maybe_swap_for_parent()` at `:319`).
- Errors are deleted after a successful sync (`src/Product/ProductHelper.php:108`).
- So variations usually have neither row, and they take the worst path.

The fix leaves exactly one postmeta row per product in the join (the `synced_at` row). The two `NOT EXISTS` lookups are bounded by the `meta_key` index. Cost per product no longer grows with its meta count.

## Prevention

- **Do not combine `NOT EXISTS` with `OR` in a `meta_query` on a hot or batched path.** Put "missing or not X" conditions in a scoped `posts_where`/`posts_clauses` filter as keyed `NOT EXISTS` subqueries. Remove the filter in a `finally` block. Keep the query on `wc_get_products()` so its filters still apply.
- **Regression guard:** `tests/Unit/Product/ProductRepositoryTest.php:403` (`test_find_expiring_product_ids_joins_postmeta_only_once`) captures the SQL from `posts_request` and asserts that the query contains exactly one `JOIN <postmeta>`.
- **Same pattern still in the codebase:** `get_sync_ready_products_meta_query()` (`src/Product/ProductRepository.php:257`, used by `find_sync_ready_products()` at `:230`) still pairs `NOT EXISTS` with `!=`. That leaves one unconstrained join, which fans out linearly rather than quadratically. Check it the same way if that path gets slow.
- **Benchmark with production-shaped meta, not convenient meta.** The stall depends on which rows are absent and on table statistics, so a fixture must match how the plugin actually writes meta. The fixture that reproduced it was generated on MariaDB with the SEQUENCE engine. It used about 600k postmeta rows, visibility rows on parents only, error rows on about 5% of products, and a few products with very large meta sets. The method had four parts:
  1. Capture the real SQL from `posts_request`.
  2. Loop the keyset cursor under `SET STATEMENT max_statement_time=... FOR`.
  3. Read `EXPLAIN` to see which alias drives the join.
  4. Prove the new results match the old ones with an `EXISTS`-form reference query, since the old query cannot finish on the stalling fixture.
- **Changing the lookup changes a public hook payload.** The `$args` that `woocommerce_gla_product_query_args` receives for this lookup now carry only the `synced_at` clause. `AGENTS.md` treats what a `woocommerce_gla_` hook receives as an exposed surface, so restructuring a query that flows through this filter needs a backward-compatibility statement in the PR.

## Related Issues

- woocommerce/google-listings-and-ads#3146 (issue, closed): the original report, with the five-join query.
- woocommerce/google-listings-and-ads#3334 (merged): moved this lookup to keyset pagination. The meta query described here was still in place after it.
