# Using a proxy for mocked data

Since data returned from the Google API can rely on actual live campaigns, the purpose of this proxy is to mock some of that data.

Prerequisites:
`npm install`

## Run proxy

```
npm run test-proxy
```

Or, if you want to use a local connect server:

```
WOOCOMMERCE_CONNECT_SERVER=http://localhost:5000 npm run test-proxy
```

### Run the proxy in a specific mode
The mode will determine what kind of responses will be returned, this is used to mock specific responses which can't be reproduced through regular requests.

Modes:
- `delete_error` will return an internal error when deleting products
- `update_error` will return an internal error when updating products

### Log responses when running the proxy
This option will allow us to view the responses which are returned from the API, this is useful for generating mocked responses to return.

```
PROXY_LOG_RESPONSES=true npm run test-proxy
```

## Connect test site to proxy

On your test site you will need to run a PHP snippet to use the proxy to handle any requests:

```php
define( 'WOOCOMMERCE_GLA_CONNECT_SERVER_URL', 'http://localhost:5500' );
```

Or, if your test site is running within a docker container, the PHP snippet would be the following instead:

```php
define( 'WOOCOMMERCE_GLA_CONNECT_SERVER_URL', 'http://host.docker.internal:5500' );
```

### Non Mac users

`host.docker.internal` works only on Mac environments. On others, you would have to use `172.17.0.1` instead of `localhost` in the PHP snippet and in `tests/proxy/config.js`.

## Available mocks

At the moment only the report data is mocked, the rest of the requests are sent on to the connect server. The mocks folder contains example responses for the reports.
