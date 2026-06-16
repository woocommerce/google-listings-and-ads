# PHPUnit

GLA unit tests live in `tests/Unit/`, mirror the `src/` directory structure, and use PHPUnit 9 with `dg/bypass-finals`.

## Test Base Classes

### `UnitTest` (most common)

Extends `WP_UnitTestCase`. Use for services, adapters, hooks, and utilities.

```php
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;

class ProductHelperTest extends UnitTest {
    protected ProductHelper $subject;

    public function setUp(): void {
        parent::setUp();
        $this->options = $this->createMock( OptionsInterface::class );
        $this->subject = new ProductHelper( $this->options );
    }

    public function test_something(): void {
        $this->options->method( 'get_merchant_id' )->willReturn( 123 );
        $this->assertEquals( 123, $this->subject->get_merchant_id() );
    }
}
```

Helpers: `$this->login_as_administrator()`, `$this->login_as_role( 'editor' )`.

### `RESTControllerUnitTest`

For REST endpoint tests. Creates an admin user in `wpSetUpBeforeClass`, provides a `WP_Test_Spy_REST_Server`, and exposes `do_request()`.

```php
class MyControllerTest extends RESTControllerUnitTest {
    protected MyController $controller;

    public function setUp(): void {
        parent::setUp();
        $this->service    = $this->createMock( MyService::class );
        $this->controller = new MyController( $this->server, $this->service );
        $this->controller->register();
    }

    public function test_get_returns_200(): void {
        $this->service->method( 'get_data' )->willReturn( [ 'id' => 1 ] );
        $response = $this->do_request( '/wc/gla/ads/my-resource', 'GET' );
        $this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 1, $response->get_data()['id'] );
    }

    public function test_post_with_params(): void {
        $response = $this->do_request( '/wc/gla/ads/my-resource', 'POST', [ 'name' => 'test' ] );
        $this->assertEquals( 201, $response->get_status() );
    }
}
```

### `ContainerAwareUnitTest`

For integration tests that need the real DI container via `$this->container`.

## Mocking

`dg/bypass-finals` allows mocking `final` classes without configuration:

```php
// Mock an interface
$mock = $this->createMock( OptionsInterface::class );

// Mock a final class (bypass-finals handles this)
$mock = $this->createMock( WCProductAdapter::class );

// Partial mock
$mock = $this->getMockBuilder( SomeClass::class )
             ->onlyMethods( [ 'some_method' ] )
             ->getMock();
```

## File Placement and Headers

```
tests/Unit/Product/ProductHelperTest.php
    mirrors →
src/Product/ProductHelper.php
```

Every test file requires:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

defined( 'ABSPATH' ) || exit;
```

## Running Tests

```bash
# All tests
composer run-script test-unit

# Single file
./vendor/bin/phpunit tests/Unit/Product/ProductHelperTest.php

# Single method
./vendor/bin/phpunit --filter test_something tests/Unit/Product/ProductHelperTest.php
```
