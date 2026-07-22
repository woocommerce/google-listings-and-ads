# AGENTS.md — Google for WooCommerce

Guidelines for AI coding agents working in this repository (the `google-listings-and-ads` plugin, published as **Google for WooCommerce**). This plugin runs on live merchant stores and its public surface is consumed by other extensions, themes, and custom site code, so backward compatibility is a release-blocking concern.

- **Namespace root:** `Automattic\WooCommerce\GoogleListingsAndAds\` (`src/`)
- **Text domain / slug:** `google-listings-and-ads`

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description**. An internal-looking name or location — including this plugin's `Internal` namespace — is not by itself a guarantee that a symbol is safe to change: other extensions, themes, and custom site code implement and consume some of these contracts in practice. See the exposed-surface list for what counts and the **Scope** note for what does not; when a symbol is genuinely reachable and useful to outside code, err toward treating it as exposed.

**Externally exposed surface** — treat changes here as high-risk:

- **Custom hooks** — the actions and filters this plugin fires (the `woocommerce_gla_` prefix). These are documented integration points for merchant site code and other extensions. Renaming a hook, changing or reordering its arguments, or dropping it breaks whatever is hooked in; to retire one, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window.
- **REST API** — the `wc/gla` routes, their request/response shapes, and their auth expectations.
- **Public PHP** — any `public` class, method, or function under `Automattic\WooCommerce\GoogleListingsAndAds\` that another plugin or theme actually autoloads and calls, plus any symbol explicitly documented as extensible.
- **Front-end globals** — the `glaData` JS global and any properties page scripts may read.

**Scope — what is *not* a third-party contract:** the dependency-injection container and the `Internal\` classes that exist only so the container can construct and wire services are internal implementation, not an API other extensions build on. An ordinary signature change to one of them does **not** require a BC statement. When you are unsure whether a symbol is a contract, check the exposed surface above rather than assuming every `public` method is one.

Rules:

- **Never add or remove a required method on an interface that external code can implement** — existing implementers fatal on load. Prefer adding the method to the concrete class, introducing a new interface, or supplying a default implementation in an abstract base class. If an interface change is unavoidable, flag it explicitly.
- **Deprecate, don't rename.** Never rename or remove an existing public symbol in place: mark it `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window.
- **Don't implement or type-hint WooCommerce core's `Internal\` classes or interfaces** — core treats them as changeable in any release. If unavoidable, guard the dependency with `class_exists()` / `interface_exists()` / `method_exists()` checks so a core change doesn't cause a fatal error in this plugin.

> Why: WooCommerce 10.9.0 was reverted on WP Cloud after woocommerce/woocommerce#64394 added a required method to core's internal `FeedInterface`, causing fatal errors in older WooCommerce Stripe Gateway versions that implemented it (fixed in woocommerce/woocommerce#65965). The same failure mode applies to any published WooCommerce extension.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. A change to any of the following is equally high-risk and needs the same backward-compatibility impact statement in the PR.

- **Global state.** Code runs in admin, REST, CLI, cron, webhook, and front-end contexts, and not all set the globals a front-end request does (`$post`, `$wp_query`, an initialized session or cart). A new read of a global — or of `WC()->…` state — in a path reachable outside a standard request fatals or silently misbehaves where it isn't set. Guard the exact dependency (`function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle) and verify `WC()` and the component are initialized before dereferencing.
- **Multisite.** Site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, capabilities, and upload paths all differ under multisite. A change that reads or writes site state must state whether it behaves correctly under multisite, or say it wasn't tested there.
- **Install layout.** WordPress can run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind `home_url()` vs `site_url()`).

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, hook, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers — you cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone may consume it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it for review.
