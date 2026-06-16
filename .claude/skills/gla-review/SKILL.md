---
name: gla-review
description: Review a pull request for the Google Listings and Ads plugin
argument-hint: <PR_NUMBER>
allowed-tools: [Read, Bash, Glob, Grep]
---

# Google for WooCommerce — Code Review

The user invoked this with: $ARGUMENTS

## Step 1 — Fetch the PR

Run both in parallel:

```
gh pr diff <PR_NUMBER>
gh pr view <PR_NUMBER> --json title,body,author,baseRefName
```

## Step 2 — Detect what changed

Scan the file paths in the diff output:

- **PHP** — any `*.php` file
- **JS/CSS** — any `*.js`, `*.jsx`, `*.ts`, `*.tsx`, `*.scss` file
- **Tests** — `tests/Unit/**` or `*.test.js` / `__tests__/`
- **Config** — `webpack.config.js`, `*.json`, `composer.json`, `package.json`

## Step 3 — Load context docs

Read only the docs that match what changed. Use the Read tool — do not skip this step.

### Always read first (regardless of what changed)

- `docs/context/review-preferences.md` — reviewer's personal patterns and priorities

### Always read when PHP files changed

- `docs/context/php/coding-style.md`
- `docs/context/php/naming-conventions.md`

### Read additionally based on which PHP directories/files changed

| If the diff touches… | Also read… |
|---|---|
| `src/Internal/DependencyManagement/` or new service classes | `docs/context/php/dependency-injection.md` |
| `src/API/Site/Controllers/` | `docs/context/php/rest-api.md` |
| `src/Options/` or `OptionsInterface` constants | `docs/context/php/options-and-storage.md` |
| `src/Jobs/` | `docs/context/php/background-jobs.md` |
| `src/Product/` | `docs/context/php/product-sync-pipeline.md` |
| `src/Google/` or `src/Ads/` or `src/MerchantCenter/` | `docs/context/php/google-api-clients.md` |
| `src/Proxies/` | `docs/context/php/proxy-pattern.md` |
| `src/Admin/` or asset registration | `docs/context/php/asset-management.md` |
| `tests/Unit/` | `docs/context/php/phpunit.md` |

### Always read when JS/CSS files changed

- `docs/context/js/component-conventions.md`
- `docs/context/js/copy-guidelines.md`

### Read additionally based on which JS directories/files changed

| If the diff touches… | Also read… |
|---|---|
| `js/src/data/` | `docs/context/js/state-management.md` |
| `js/src/data/controls.js` | `docs/context/js/data-store-controls.md` |
| `js/src/hooks/` | `docs/context/js/hooks.md` |
| `js/src/utils/` | `docs/context/js/utils.md` |
| `js/src/pages/` or routing | `docs/context/js/routing-and-navigation.md` |
| Tracks / event recording | `docs/context/js/event-tracking.md` |
| `*.test.js` or `__tests__/` | `docs/context/js/tests.md` |
| `glaData` or `add_inline_script` | `docs/context/js/php-js-bridge.md` |
| `webpack.config.js` or entry points | `docs/context/js/build-system.md` |
| Feature flag checks (`glaData.*`) | `docs/context/js/feature-flags.md` |
| `js/src/components/tours/` or `GuideModal` | `docs/context/js/tours-and-guides.md` |

## Step 4 — Review

Apply the checklists below against the loaded context and the diff.

### PHP checklist

**Security**
- Late escaping at output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Inputs sanitized: `sanitize_text_field()`, `absint()`, `sanitize_email()`
- Nonces and capabilities checked on sensitive routes
- `$wpdb->prepare()` always; `permission_callback` never `__return_true` on sensitive routes

**Conventions**
- File header: `defined( 'ABSPATH' ) || exit;`
- Hooks prefixed `woocommerce_gla_` or `gla/`
- Options via `OptionsInterface` constants — never raw `get_option()` / `update_option()`
- New services: constructor injection, registered in a service provider
- Hook callbacks: public domain-name methods calling protected `handle_*` implementations
- Yoda conditions; `??` over `isset()`; `use` statements over inline-qualified names

**Naming**
- Parameter and variable names are descriptive — single-letter or abbreviated names need justification
- Hook names and method names are descriptive but not redundant given their class/domain context
- Comments are accurate and explain WHY, not WHAT — flag stale or misleading comments

**Control flow**
- Variables initialized just before first use, not at the top of a long function
- Intermediate booleans that act as one-step relays — consolidate unless the name genuinely adds clarity
- Strict comparisons (`===` / `!==`) — flag loose comparisons where strictness matters

**Performance**
- Transients have expiry; non-global options use `autoload => false`
- No `posts_per_page => -1`
- HPOS: `wc_get_orders()` / `$order->get_*()`, not raw post queries

### JS checklist

**Imports and externalization**
- `@wordpress/element` — never `react` directly
- `apiFetch` for all REST — never raw `fetch()`
- `@wordpress/*` / `@woocommerce/*` not bundled — flag any that are (except `@wordpress/dataviews`)
- Path alias `~` for `js/src/`; lodash per-method: `import debounce from 'lodash/debounce'`

**React patterns**
- `useSelect` for reads, `useDispatch` for writes — never dispatch inside `useSelect`
- `useAppSelectDispatch` for data that needs `isResolving` / `hasFinishedResolution`
- No direct DOM manipulation — use refs or React state
- New components without Jest unit tests are a flag

**State and side effects**
- API calls have error handling — fire-and-forget is a flag
- Optimistic UI updates account for failure
- Side effects ordered so that state reflects reality at every step

**Control flow and naming**
- Variables initialized just before first use
- Strict comparisons — flag `=== false` where it may be fragile vs. intentional
- Array index as `key` prop — flag unless the list is static and never reordered

**SCSS**
- Reuse existing SCSS variables rather than hardcoding values
- No magic numbers for colors, spacing, or breakpoints that already have a variable

**Copy**
- All UI strings: sentence case
- Brand names keep official casing: "Google for WooCommerce", "Merchant Center", "Google Ads", "Performance Max"
- Acronyms uppercase: API, URL, CSV, ID, SKU, GTIN

**Tests**
- New components and hooks have co-located `*.test.js` files — flag if missing
- Tests cover the primary happy path and at least one error/edge case

## Step 5 — Output

```
## Summary
2–3 sentences. ✅ Approve / 🔄 Request Changes / 💬 Comment

## Critical 🔴
Category · `file:line` · Problem · Fix

## Improvements 🟡
`file:line` · Problem · Recommendation

## Nits 🔵
Style or naming only.

## Praise ✨
1–3 things done well — always include.
```

Omit any section that has no items (except Praise — always include).

Each finding must be actionable and explain the why, not just the what.
