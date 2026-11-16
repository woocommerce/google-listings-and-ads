---
title: "fix: Use the market exchange rate when validating secondary-market prices"
type: fix
status: completed
date: 2026-11-16
origin: GOOWOO-1188
---

# fix: Use the market exchange rate when validating secondary-market prices

## Problem Frame

A secondary market can carry a fixed `exchange_rate` so its products keep syncing when WPML cannot convert a price. `BatchProductHelper::generate_secondary_market_entries()` reads the rate and passes it to the emission step (`WCProductInputAdapter`), but validation runs first through `ProductFactory::create()` → `WCProductAdapter`, which never receives the rate. When WPML returns no converted price, `WCProductAdapter::map_wc_product_price()` leaves `price` unset, the `NotNull` constraint fails, the product is marked invalid and skipped:

`Skipping product (ID: 11) for market cz currency CZK because it does not pass validation: {"errors":["[price] This value should not be null."]}`

Affects every WPML store where WPML can't convert, and every store without WPML that relies on a rate-only secondary market. The merchant in the ticket has 575+ skips across CZ/SK/DE. Products marked invalid also drop out of the periodic resync until the error meta is cleared (that clears itself once a sync succeeds).

## Requirements

- R1. When a secondary market has `exchange_rate > 0` and WPML returns no converted regular price, validation prices the product at `regular_price × exchange_rate` in the market currency (ticket example: 55.61 × 5.6354 = 313.38 CZK) and the product passes the price check.
- R2. Same fallback for the sale price: when WPML returns no converted sale price and the rate is set, the sale price is `sale_price × exchange_rate`, matching `WCProductInputAdapter::map_sale_price()`.
- R3. Markets with no rate keep today's behavior: no converted price → price unset → product skipped (never emitted with a store-currency amount under another currency's label).
- R4. No change for primary market / store-currency entries.
- R5. Backward compatible: additive, optional parameters only.

## Scope Boundaries

- Not unifying `WCProductAdapter` and `WCProductInputAdapter` pricing/tax logic (validation still uses `wc_get_price_*`, emission uses target-country tax). Validation only needs a non-null price.
- Not clearing `_wc_gla_errors` on already-invalid products — a successful sync after the fix overwrites it; a full sync re-processes them.
- GOOWOO-1132 / 1189 / 1254 are separate issues.

---

## Key Technical Decisions

- **Pass the rate through `ProductFactory::create()` as a new trailing optional `float $exchange_rate = 0.0`.** `ProductFactory::create()` is public; appending an optional parameter is additive for callers. The one theoretical break is a third-party subclass overriding `create()` without the new parameter (PHP fatals on an incompatible declaration). `ProductFactory` is a container-built service, not a documented extension point, and the same method already gained `$language` and `$currency_override` this way in 3.9.0 (c0b7cb071) — follow that precedent and state the impact in the PR.
- **Carry the rate into `WCProductAdapter` as a new `exchange_rate` property key in `mapTypes()`**, the same way `currency_override` and `wpml` are handled (stored on the object, then `unset` before `parent::mapTypes()` so it never reaches the Google payload).
- **Fallback rule mirrors `WCProductInputAdapter`:** converted = WPML conversion (when WPML is injected) ?? (rate > 0 && regular price non-empty ? regular × rate : null). If still null → leave price unset (R3).
- **No-WPML case:** today, with `currency_override` set and no WPML instance, `WCProductAdapter` falls through to store-currency pricing (covered by `test_currency_override_without_wpml_instance_falls_back_to_store_currency`). Keep that exact behavior when no rate is set; when a rate is set, use the rate. In production WPML is always injected by the container, so this only matters for direct callers/tests.
- **Overridable methods stay on every path.** `map_wc_product_price()` and `map_wc_product_sale_price()` are protected and keep being called in the same order; the change is inside their override branch.

---

## Implementation Units

### U1. Accept and apply the exchange rate in `WCProductAdapter`

**Goal:** Validation-side adapter computes a converted price from the rate when WPML gives none.

**Requirements:** R1, R2, R3, R4

**Dependencies:** none

**Files:**
- `src/Product/WCProductAdapter.php`
- `tests/Unit/Product/WCProductAdapterTest.php`

**Approach:**
- New `protected float $exchange_rate = 0.0;` populated from `$properties['exchange_rate']` (coerced to float, non-positive → 0.0) and unset before `parent::mapTypes()`.
- Override branch condition becomes "currency override set and (WPML injected or rate > 0)".
- Regular price: WPML conversion if available, else rate fallback on `get_regular_price()` when non-empty; null → return without setting price.
- Sale price: same pattern — WPML sale conversion, else rate × the base sale price (with the existing active-price promotion and ended-sale guard applied for the rate path so it matches the emission adapter). Sale dates: WPML per-currency dates when available, else base product dates.

**Patterns to follow:** `WCProductInputAdapter::map_price()` / `map_sale_price()`.

**Test scenarios:**
- Rate 5.6354, WPML returns null, regular 55.61 → price `313.38`-ish value in CZK (assert currency CZK and value ≈ 55.61 × 5.6354).
- Rate set, no WPML instance at all → converted price in override currency.
- Rate set, WPML returns a converted price → WPML value wins over the rate.
- No rate, WPML returns null → price unset (existing test stays green).
- Rate set, product on sale (sale 40, regular 55.61), WPML null → sale price = 40 × rate in override currency.
- Rate set, sale ended in the past → no sale price.
- Rate set, empty regular price → price unset.
- No currency override → rate ignored, store currency used.

**Verification:** New and existing `WCProductAdapterTest` cases pass.

### U2. Thread the rate through `ProductFactory` and `BatchProductHelper`

**Goal:** Validation receives the same rate the emission step already gets.

**Requirements:** R1, R5

**Dependencies:** U1

**Files:**
- `src/Product/ProductFactory.php`
- `src/Product/BatchProductHelper.php`
- `tests/Unit/Product/ProductFactoryTest.php`
- `tests/Unit/Product/BatchProductHelperTest.php`

**Approach:**
- `ProductFactory::create()` gains trailing `float $exchange_rate = 0.0`, passed into the adapter properties as `exchange_rate`.
- `generate_secondary_market_entries()` passes `$market_exchange_rate` to `create()` (only meaningful when `$currency_override` is non-empty; the adapter ignores it otherwise).
- Update the docblock comment near the exchange-rate read to state it covers validation too.

**Test scenarios:**
- `ProductFactory::create()` with override + rate and a WPML stub returning null → adapter price is converted.
- `BatchProductHelperTest`: secondary market with `exchange_rate`, WPML returns null, **real validator** (or a validator that checks the adapter's price is non-null) → one secondary entry emitted, not skipped. This is the regression test for the ticket; the existing exchange-rate test stubs `validate` to `[]`, which is why the bug went unnoticed.
- Existing test that captures factory args (around the `$captured_secondary_args` closure) updated to accept/record the new argument.

**Verification:** Full `tests/Unit/Product` suite passes; PHPCS clean on touched files.

---

## System-Wide Impact

- **BC:** `ProductFactory::create()` — appended optional parameter, additive. `WCProductAdapter` — new optional property key; protected method signatures unchanged; overridable methods still called on every path. No hooks, REST, or JS changes.
- **Multisite:** no option reads/writes added.
- **Behavior change:** products in rate-backed secondary markets that were being skipped will now sync. Previously-invalid products recover on their next sync.

## Risks

- Validation and emission compute tax differently, so the validated price value may differ slightly from the sent value. Only the presence of a price is validated, so this is harmless.
