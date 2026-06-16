# Naming Conventions

## File Header

Every PHP file in `src/` and `tests/`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

defined( 'ABSPATH' ) || exit;
```

The `declare(strict_types=1)` and namespace appear before `defined()`. The blank line before `defined()` is required.

## Namespace

Root namespace: `Automattic\WooCommerce\GoogleListingsAndAds` → maps to `src/`

```
src/Product/ProductHelper.php
    → Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper

tests/Unit/Product/ProductHelperTest.php
    → Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\ProductHelperTest
```

## Hook Naming

**All** actions and filters use the `woocommerce_gla_` or `gla/` prefix:

```php
// Actions
do_action( 'woocommerce_gla_onboarding_completed' );
do_action( 'woocommerce_gla_ads_client_exception', $exception, __METHOD__ );
do_action( "woocommerce_gla_options_updated_{$option_name}", $value );

// Filters
apply_filters( 'woocommerce_gla_product_attribute_values', $values, $product );
apply_filters( 'woocommerce_gla_force_run_install', false );

// Background job hooks (ActionScheduler)
// gla/jobs/{job_name}/process_item — generated automatically from get_name()
```

Never use bare names, WordPress-prefixed names, or woocommerce-prefixed names without `gla`.

## Class Naming

| Type | Convention | Example |
|---|---|---|
| Classes | `PascalCase` | `ProductSyncer`, `MerchantCenterService` |
| Interfaces | `{Name}Interface` or `{Name}AwareInterface` | `OptionsInterface`, `OptionsAwareInterface` |
| Traits | `{Name}Trait` or `{Name}AwareTrait` | `OptionsAwareTrait`, `PluginHelper` |
| Abstract classes | `Abstract{Name}` | `AbstractActionSchedulerJob` |
| Exceptions | `{Name}Exception` | `ApiNotReachableException` |

## Method Visibility and Structure

- **Default to `private`** for all new methods; widen only when necessary
- **`protected`** only when subclasses demonstrably need it
- **`public`** only for methods called from outside the class
- **No standalone functions** — always use class methods (standalone functions can't be mocked in unit tests)

## Imports

Always add a `use` statement; never inline-qualify class names:

```php
// Correct
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
$this->options->get( OptionsInterface::MERCHANT_ID );

// Wrong — fully qualified inline
\Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface::MERCHANT_ID;
```

## Option Keys

Always use `OptionsInterface` constants — never bare strings:

```php
// Correct
$this->options->get( OptionsInterface::MERCHANT_ID );

// Wrong
$this->options->get( 'merchant_id' );
```

## Text Domain

Always `google-listings-and-ads` as a literal string:

```php
// Correct
__( 'Connect your account', 'google-listings-and-ads' );

// Wrong — variable text domain
__( 'Connect your account', $this->get_text_domain() );
```

## Compatibility Comments

When working around a version-specific WooCommerce or WordPress behavior:

```php
// compatibility-code "WC < 9.2" -- WC_Order::get_billing_country() not available before 9.2
$country = method_exists( $order, 'get_billing_country' )
    ? $order->get_billing_country()
    : get_post_meta( $order->get_id(), '_billing_country', true );
```

## Changelog Prefixes

- `* Add -` new feature
- `* Fix -` bug fix
- `* Tweak -` minor behavior change
- `* Update -` update to existing feature
- `* Dev -` developer-facing change (hook, filter, API)
