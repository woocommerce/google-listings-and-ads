# Proxy Pattern

GLA wraps all WordPress and WooCommerce global function calls behind proxy classes to keep service code testable.

## Why Proxies

Direct calls to `WC()`, `wp_remote_get()`, or `WP_REST_Server` methods in service classes make unit testing impossible (globals can't be mocked). Proxy classes are registered in the container and can be replaced with mocks in tests.

## Available Proxies (`src/Proxies/`)

| Proxy | Wraps | Injected Via |
|---|---|---|
| `WP` | WordPress core functions | `WPAwareTrait` |
| `WC` | WooCommerce functions + `WC()` | `WCAwareTrait` |
| `RESTServer` | `WP_REST_Server` | Constructor injection in `BaseController` |
| `Tracks` | `WC_Tracks::record_event()` | Constructor injection |

## Setter Injection

```php
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\WPAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\WPAwareInterface;

class MyService implements WPAwareInterface {
    use WPAwareTrait;  // provides $this->wp and set_wp_proxy()

    public function get_user(): WP_User {
        return $this->wp->get_user_by( 'id', 1 );
    }
}

// In service provider
$this->share( MyService::class )
     ->addMethodCall( 'set_wp_proxy', [ WP::class ] );
```

## Rules

- Never call `WC()`, `wc_*()`, or `wp_*()` global functions from inside classes under `src/`
- Never access `$GLOBALS['wp_query']`, `$_POST`, `$_GET` directly — use the proxy or `Request` objects
- `RESTServer` is already constructor-injected in `BaseController` — no setter needed for controllers
- `Tracks` proxy wraps `WC_Tracks::record_event()` for analytics; use it instead of calling `WC_Tracks` directly

## In Tests

```php
// Replace the real proxy with a mock
$this->wp_mock = $this->createMock( WP::class );
$this->subject->set_wp_proxy( $this->wp_mock );

$this->wp_mock->method( 'get_user_by' )->willReturn( $fake_user );
```

## RESTServer

`BaseController` receives `RESTServer` via constructor injection:

```php
class MyController extends BaseController {
    // RESTServer is already available as $this->server — don't add it to your constructor args
    public function register_routes(): void {
        $this->register_route( 'my/route', [ ... ] );  // delegates to $this->server
    }
}
```

`RESTServiceProvider` wires `RESTServer` automatically — no manual binding needed.
