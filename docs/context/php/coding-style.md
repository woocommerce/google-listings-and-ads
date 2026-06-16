# Coding Style

## Method Naming and Visibility

Use `snake_case` for methods and variables. Default to `private`; only widen visibility when necessary.

```php
class ProductSyncer {
    // private: internal helpers
    private function validate_product( WC_Product $product ): bool { ... }

    // protected: only when subclasses need it
    protected function get_batch_size(): int { ... }

    // public: only for callers outside this class
    public function sync( array $products ): void { ... }
}
```

## Static Methods

Pure methods (output depends only on inputs, no `$this`, no I/O) must be `static`:

```php
// Pure — depends only on inputs → static
public static function format_price( float $amount, string $currency ): string {
    return $currency . ' ' . number_format( $amount, 2 );
}

// Uses $this or external state → never static
public function get_merchant_id(): int {
    return $this->options->get_merchant_id();
}
```

## Hook Callbacks

Public hook callbacks use descriptive domain names (not a `handle_` prefix). Internal implementation methods that do the actual work use the `handle_` prefix:

```php
public function register(): void {
    // when a product is updated, schedule a sync job
    add_action( 'woocommerce_update_product', [ $this, 'update_by_object' ], 90, 2 );
}

// Public hook callback — descriptive domain name
public function update_by_object( int $product_id, WC_Product $product ): void {
    $this->handle_update_products( [ $product ] );
}

// Protected internal implementation — handle_ prefix
protected function handle_update_products( array $products ): void {
    // actual sync logic
}
```

Comments above `add_action`/`add_filter` calls explain the business reason (why this hook, not what it does).

## Hook Docblocks

Hooks fired with `do_action` or `apply_filters` must have a docblock:

```php
/**
 * Fires after all GLA products have been synced to Merchant Center.
 *
 * @param int[] $synced_ids Product IDs that were successfully synced.
 */
do_action( 'woocommerce_gla_products_synced', $synced_ids );

/**
 * Filters product attributes before syncing to Merchant Center.
 *
 * @param array      $attributes The attribute values.
 * @param WC_Product $product    The WooCommerce product.
 */
$attributes = apply_filters( 'woocommerce_gla_product_attribute_values', $attributes, $product );
```

## WordPress Coding Standards

```php
// Yoda conditions — constant/literal on the left
if ( null === $merchant_id ) { ... }
if ( 'active' === $status ) { ... }

// Null coalescing over isset
$value = $options['key'] ?? 'default';          // Good
if ( isset( $options['key'] ) ) { ... }         // Avoid

// Ternary for simple assignments
$label = $is_active ? 'Active' : 'Inactive';

// call_user_func_array — positional (indexed) args
call_user_func_array( [ $obj, 'method' ], [ $arg1, $arg2 ] );  // Good
call_user_func_array( [ $obj, 'method' ], [ 'key' => $arg ] ); // Wrong — keys ignored
```

## Docblocks

Keep docblocks concise — one line for simple methods. Include `@param` and `@return` where types aren't obvious from the signature.

```php
// Good — concise
/**
 * Check if the Merchant Center account is connected.
 */
public function is_connected(): bool { ... }

// Good — when param context adds value
/**
 * Schedule a product sync for the given IDs.
 *
 * @param int[] $product_ids WooCommerce product IDs to sync.
 */
public function schedule_sync( array $product_ids ): void { ... }

// Avoid — restating what the code already says
/**
 * This method gets the merchant ID by calling options->get_merchant_id()
 * and returns the result as an integer.
 */
```

## Code Clarity

Comments are for non-obvious WHY, not WHAT:

```php
// Good — explains a hidden reason
// must check this before querying MC — API throws on unclaimed accounts
if ( ! $this->is_claimed() ) {
    return;
}

// Avoid — restating what the code says
// check if account is claimed
if ( ! $this->is_claimed() ) {
    return;
}
```

## Data Integrity

Always verify entity state before delete or modify:

```php
// Good — verify before acting
public function delete_campaign( int $campaign_id ): void {
    $campaign = $this->ads->get_campaign( $campaign_id );

    if ( ! $campaign ) {
        return;
    }

    if ( 'removed' === $campaign->get_status() ) {
        throw new InvalidValue( 'Campaign is already removed.' );
    }

    $this->ads->delete_campaign( $campaign_id );
}

// Bad — no verification
public function delete_campaign( int $campaign_id ): void {
    $this->ads->delete_campaign( $campaign_id );  // Could remove wrong campaign
}
```

Checklist before delete/modify operations:
- Verify the entity exists
- Verify its state (status, type)
- Check ownership if user-scoped
- Check the return value — don't assume success

## Linting

Only fix linting errors in code you added or modified. Never sweep unrelated files for style fixes unless explicitly asked.

## No Standalone Functions

All new logic goes in class methods. Standalone functions cannot be mocked in unit tests.

```php
// Wrong
function gla_format_price( float $amount ): string { ... }

// Correct
class PriceFormatter {
    public static function format( float $amount ): string { ... }
}
```
