# AGENTS.md — Google for WooCommerce

Guidelines for AI coding agents working in this repository (the `google-listings-and-ads` plugin, published as **Google for WooCommerce**). This plugin runs on live merchant stores and its public surface is consumed by other extensions, themes, and custom site code, so backward compatibility is a release-blocking concern.

- **Namespace root:** `Automattic\WooCommerce\GoogleListingsAndAds\` (`src/`)
- **Text domain / slug:** `google-listings-and-ads`

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, method, hook, or REST endpoint signature is **high-risk** and **must state its backward-compatibility impact in the PR description** — regardless of whether the symbol lives in the `Internal` namespace. The `Internal` namespace is not a guarantee that a symbol is safe to change: third-party code implements and consumes some of these contracts in practice.

Treat a symbol as **externally exposed** when it is implemented or consumed outside this plugin — by other extensions, themes, or custom site code — even if it lives under `Internal`. WordPress actions and filters this plugin fires or registers count as externally exposed: renaming a hook, changing its arguments, or dropping it breaks whatever is hooked in. When in doubt, assume it is exposed and state the BC impact.

**Adding a method to an interface that external code can implement must be flagged explicitly.** It is a backward-incompatible change: existing implementers fatal on load because they no longer satisfy the contract. Likewise, **removing a required method from an interface is breaking** for existing implementers. Prefer a non-breaking alternative — add the method to the concrete class rather than the interface, introduce a separate new interface, or supply a default implementation via an abstract base class.

**Deprecate, don't rename.** For existing public symbols (classes, interfaces, methods, constants, hooks), never rename or remove them in place. Mark the old symbol `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers have time to migrate.

> Why this matters: a signature change to a shared contract can take down live stores. WooCommerce 10.9.0 was reverted on WP Cloud after PR #64394 added a required `get_entry_count(): int` method to `FeedInterface`, fataling older WooCommerce Stripe Gateway versions that implemented it (fixed in PR #65965). The same failure mode applies to any published WooCommerce extension.
