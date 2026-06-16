# Options and Storage

GLA manages all persistent plugin data through `OptionsInterface`. **Never call WordPress option functions directly.**

## OptionsInterface

```php
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

// Read
$merchant_id = $this->options->get( OptionsInterface::MERCHANT_ID );
$merchant_id = $this->options->get_merchant_id();  // shorthand

// Write
$this->options->update( OptionsInterface::MERCHANT_ID, 12345 );
$this->options->add( OptionsInterface::MERCHANT_ID, 12345 );   // only if not set
$this->options->delete( OptionsInterface::MERCHANT_ID );
```

Never: `get_option()`, `update_option()`, `add_option()`, `delete_option()`.

## Valid Option Keys

All keys are constants on `OptionsInterface`. Only keys listed in `VALID_OPTIONS` can be stored — passing an unknown key throws an exception.

Selected keys and their purpose:

| Constant | Value | Notes |
|---|---|---|
| `ADS_ID` | `ads_id` | Google Ads account ID (PositiveInteger) |
| `MERCHANT_ID` | `merchant_id` | Merchant Center ID (PositiveInteger) |
| `GOOGLE_CONNECTED` | `google_connected` | OAuth connection state |
| `MC_SETUP_COMPLETED_AT` | `mc_setup_completed_at` | Onboarding timestamp |
| `ADS_SETUP_COMPLETED_AT` | `ads_setup_completed_at` | Ads onboarding timestamp |
| `TARGET_AUDIENCE` | `target_audience` | Free listings target countries |
| `MERCHANT_CENTER` | `merchant_center` | MC settings blob |
| `TOURS` | `tours` | UI tour dismissed states |
| `IS_SERVICE_BASED_MERCHANT` | `is_service_based_merchant` | Service-based merchant flag |

`OPTION_TYPES` maps `ADS_ID` and `MERCHANT_ID` to `PositiveInteger::class` — values are automatically cast on read.

## Adding a New Option

1. Add a constant to `OptionsInterface`: `public const MY_OPTION = 'my_option';`
2. Add it to `VALID_OPTIONS`: `self::MY_OPTION => true,`
3. If it needs type coercion, add to `OPTION_TYPES`: `self::MY_OPTION => SomeValueClass::class,`
4. Options fire hooks automatically: `woocommerce_gla_options_updated_my_option` and `woocommerce_gla_options_deleted_my_option`

## Setter Injection (OptionsAwareTrait)

```php
// 1. Implement + use
class MyService implements OptionsAwareInterface {
    use OptionsAwareTrait;  // adds $this->options and set_options_object()
}

// 2. In service provider
$this->share( MyService::class )
     ->addMethodCall( 'set_options_object', [ OptionsInterface::class ] );

// 3. In tests
$this->options_mock = $this->createMock( OptionsInterface::class );
$this->subject->set_options_object( $this->options_mock );
```

## Transients

Same pattern via `TransientsInterface` / `TransientsAwareTrait`:

```php
class MyService implements TransientsAwareInterface {
    use TransientsAwareTrait;  // adds $this->transients

    public function get_cached(): mixed {
        return $this->transients->get( TransientsInterface::MY_KEY );
    }
}
```

Transient keys follow the same constant + whitelist pattern on `TransientsInterface`.
