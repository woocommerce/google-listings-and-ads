Mocking responses for development & testing
---

This folder contains set of sample data, that could be used to mock server-side API responses for development end testing.
You can use them by using `woocommerce_gla_prepared_response_{route}` filter, like the following snippet

```php
add_filter(
	'woocommerce_gla_prepared_response_mc-reports-products',
	function( $response ) {
		return json_decode( file_get_contents( __DIR__ . '/google-listings-and-ads/tests/mocks/mc/reports/products.json' ), true ) ?: [];
	},
);
```

Please, make sure that you snippet points to `google-listings-and-ads` directory and the mock file.

This filter only allows you to adjust the final response that is returned by the API. So if we are not connected we would never reach this point and an error will be returned.
