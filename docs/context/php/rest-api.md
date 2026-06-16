# REST API

All GLA REST endpoints live under the `/wc/gla` namespace and are registered via controllers that extend `BaseController`.

## Base Class

```php
namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;

class MyController extends BaseController {
    public function __construct( RESTServer $server, MyService $service ) {
        parent::__construct( $server );
        $this->service = $service;
    }

    public function register_routes(): void {
        $this->register_route(
            'ads/my-resource',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_items_callback(),
                    'permission_callback' => $this->get_permission_callback(),
                ],
                'schema' => $this->get_api_response_schema_callback(),
            ]
        );
    }

    protected function get_schema_properties(): array {
        return [
            'id' => [
                'type'        => 'integer',
                'description' => __( 'Resource ID.', 'google-listings-and-ads' ),
                'context'     => [ 'view' ],
            ],
        ];
    }

    protected function get_schema_title(): string {
        return 'my_resource';
    }
}
```

## Key Contracts

- `register_routes()` is abstract — must implement
- `get_schema_properties()` and `get_schema_title()` are abstract — must implement
- Never call `register_rest_route()` directly — always use `$this->register_route()`
- Never omit `permission_callback` — `get_permission_callback()` checks `can_manage()` (manage_woocommerce capability)
- `ResponseFromExceptionTrait` is already mixed in — plugin exceptions auto-convert to WP_REST_Response errors

## Namespace

The namespace is derived from `PluginHelper::get_slug()` → `wc/gla`. The full endpoint URL is `/wp-json/wc/gla/{route}`.

## File Placement

```
src/API/Site/Controllers/
├── Ads/               # Google Ads controllers
├── MerchantCenter/    # Merchant Center controllers
├── Shipping/          # Shipping controllers
└── ControllerName.php # top-level for cross-domain
```

## Registration

Add to `src/Internal/DependencyManagement/RESTServiceProvider.php`:

```php
protected $provides = [
    // ... existing ...
    MyController::class => true,
];

public function register(): void {
    // ... existing ...
    $this->share( MyController::class, MyService::class );
}
```

The `RESTServer` dependency is automatically injected by `BaseController::__construct()` — do not add it to `share()` arguments.

## Testing

Extend `RESTControllerUnitTest`:

```php
class MyControllerTest extends RESTControllerUnitTest {
    public function setUp(): void {
        parent::setUp();
        $this->service    = $this->createMock( MyService::class );
        $this->controller = new MyController( $this->server, $this->service );
        $this->controller->register();
    }

    public function test_route_registered(): void {
        $this->assertArrayHasKey( '/wc/gla/ads/my-resource', $this->server->get_routes() );
    }

    public function test_get_items(): void {
        $this->service->method( 'get_items' )->willReturn( [ 'id' => 1 ] );
        $response = $this->do_request( '/wc/gla/ads/my-resource' );
        $this->assertEquals( 200, $response->get_status() );
    }
}
```

`wpSetUpBeforeClass` in `RESTControllerUnitTest` creates an admin user and sets it as current — do not replicate this.
