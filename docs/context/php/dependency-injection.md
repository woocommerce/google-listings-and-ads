# Dependency Injection

GLA uses League\Container (PSR-11), vendor-namespaced to avoid conflicts. Services are registered in 9 service providers under `src/Internal/DependencyManagement/`.

## Container Access

```php
// Runtime access — only call outside service classes (e.g., plugin bootstrap)
$container = woogle_get_container();

// Never use wc_get_container() — that's WooCommerce Core, not GLA
```

## AbstractServiceProvider

All providers extend `AbstractServiceProvider` (not the League base directly):

```php
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\DependencyManagement\AbstractServiceProvider;

class MyServiceProvider extends AbstractServiceProvider {
    // REQUIRED: every class registered in register() must appear here
    protected $provides = [
        MyService::class => true,
        AnotherService::class => true,
    ];

    public function register(): void {
        $this->share( MyService::class );
        $this->share( AnotherService::class, SomeDependency::class );
    }
}
```

The container calls `provides()` before `register()`. If a class is missing from `$provides`, the container will silently ignore this provider for that class — always keep `$provides` in sync.

## Registration Methods

```php
// Singleton — same instance returned every time (use this by default)
$this->share( MyService::class );
$this->share( MyService::class, Dep1::class, Dep2::class );  // explicit constructor args

// New instance per request — rarely needed
$this->add( MyService::class );

// Register interface → concrete mapping
$this->share_concrete( MyInterface::class, MyConcreteClass::class );

// Share + auto-tag all implemented interfaces (used for jobs, registerable services)
$this->share_with_tags( MyService::class );

// Conditional: only registers if MyService::is_needed() returns true
$this->conditionally_share_with_tags( MyService::class );
```

Constructor arguments are resolved automatically by type hint — list only non-injectable dependencies (e.g., scalar values) as explicit arguments.

## Service Provider Responsibilities

| Provider | Owns |
|---|---|
| `CoreServiceProvider` | Main domain services (Options, Transients, MC, Ads, etc.) |
| `RESTServiceProvider` | All REST API controllers |
| `GoogleServiceProvider` | Google API client wrappers, auth middleware |
| `JobServiceProvider` | ActionScheduler background jobs |
| `ProxyServiceProvider` | WP, WC, RESTServer, Tracks proxy wrappers |
| `AdminServiceProvider` | Admin pages, AssetsHandler, Admin |
| `DBServiceProvider` | Custom DB table classes and query objects |
| `ThirdPartyServiceProvider` | WooCommerce integrations (WPML, etc.) |
| `IntegrationServiceProvider` | External service integrations |

## Aware Pattern (Setter Injection)

Used when constructor injection would create circular dependencies or when the dependency is optional:

```php
// 1. Implement the interface + use the trait
class MyService implements OptionsAwareInterface {
    use OptionsAwareTrait;  // provides set_options_object() and $this->options
}

// 2. In the service provider — call addMethodCall after share()
$this->share( MyService::class )
     ->addMethodCall( 'set_options_object', [ OptionsInterface::class ] );
```

Common aware pairs:
- `OptionsAwareInterface` / `OptionsAwareTrait` — `$this->options`
- `TransientsAwareInterface` / `TransientsAwareTrait` — `$this->transients`
- `WPAwareInterface` / `WPAwareTrait` — `$this->wp`
- `WCAwareInterface` / `WCAwareTrait` — `$this->wc`

## Rules

- Never use `new MyService(...)` inside another service class
- Never call `woogle_get_container()` inside a service — inject via constructor or aware pattern
- Add new services to the appropriate provider's `$provides` array and `register()` method
