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
WOOCOMMERCE_CONNECT_SERVER=http://localhost:5500 npm run test-proxy
```

### Define host for the proxy
To run the proxy using a custom host you can start it as follows:

```
PROXY_HOST='127.0.0.1' npm run test-proxy
```

### Define port for the proxy
To run the proxy using a custom port you can start it as follows:

```
PROXY_PORT=50505 npm run test-proxy
```

### Run the proxy in a specific mode
The mode will determine what kind of responses will be returned, this is used to mock specific responses which can't be reproduced through regular requests.

Modes:

- `access_error` will return a 403 error when getting market insights data (i.e. price benchmarks)
- `delete_error` will return an internal error when deleting products
- `update_error` will return an internal error when updating products
- `account_restricted` will return a Google Business Profile account-restriction error (code 1301, `OPERATION_UNSUPPORTED_UNDER_ACCOUNT_CONDITION`) when creating a local post

```
PROXY_MODE=<access_error|delete_error|update_error|account_restricted> npm run test-proxy
```

### Log responses when running the proxy
This option will allow us to view the responses which are returned from the API, this is useful for generating mocked responses to return.

```
PROXY_LOG_RESPONSES=true npm run test-proxy
```

## Connect test site to proxy

On your test site you will need to run a PHP snippet to use the proxy to handle any requests:

```php
define( 'WOOCOMMERCE_GLA_CONNECT_SERVER_URL', 'http://localhost:5555' );
```

Or, if your test site is running within a docker container, the PHP snippet would be the following instead:

```php
define( 'WOOCOMMERCE_GLA_CONNECT_SERVER_URL', 'http://host.docker.internal:5555' );
```

### Non Mac users

`host.docker.internal` works only on Mac environments. On others, you would have to use `172.17.0.1` instead of `localhost` in the PHP snippet and in `tests/proxy/config.js`.

## Available mocks

At the moment the report data and Google Business Profile accounts/locations/local-posts are mocked, the rest of the requests are sent on to the connect server. The mocks folder contains example responses for the reports, and for Business Profile's `accounts.list`, `accounts.locations.list`, `locations.getVoiceOfMerchantState`, and the `localPosts` create/get/patch/delete operations.

### Google Business Profile — full endpoint inventory

**v1 (Account Management, Business Information, Verifications APIs):**

| Operation | Used by | Mocked? |
|---|---|---|
| `accounts.list` | Selecting an eligible location | Yes |
| `accounts.locations.list` | Selecting an eligible location | Yes |
| `locations.getVoiceOfMerchantState` | Determining whether a location is eligible to publish, and filtering out ineligible ones | Yes — three selectable states (see below) |

Granting access and disconnecting only involve the OAuth consent flow itself — a real, Google-hosted redirect, never proxied through the Connect Server — so neither needs a mock here, matching the same precedent already established for Search Console/Tag Manager's own connect flows.

**v4 (legacy My Business API, local posts):**

| Operation | Used by | Mocked? |
|---|---|---|
| `localPosts.create` | Publishing a post (from either editor), including sync failure handling | Yes — including an account-restriction failure mode via `PROXY_MODE` |
| `localPosts.get` | Reading back a post's outcome/status | Yes — three selectable states (see below) |
| `localPosts.patch` | Updating a previously synced post | Yes |
| `localPosts.delete` | Deleting a synced post | Yes |
| `localPosts.list` | Not used by any current feature's behavior — only useful for ad hoc inspection of what actually exists on a location | **Deliberately not mocked** — falls through to the real Connect Server |

**Selectable states:**

- Three fixed location IDs select the three eligibility states `getVoiceOfMerchantState` can return: `locations/1111` (eligible, the default for any other ID), `locations/2222` (unverified), `locations/3333` (suspended). `LOCATION_DISABLED_FOR_LOCAL_POST_API` — once a fourth candidate state — is not mocked; Google confirmed the code is deprecated and won't occur.
- Three fixed post IDs select the three states a `localPosts.get` read-back can return: `localPosts/9001` (processing, the default for any other ID), `localPosts/9002` (live), `localPosts/9003` (rejected).
- `PROXY_MODE=account_restricted` makes `localPosts.create` return an account-restriction error instead of succeeding — see Modes above. This is the one failure category Google gave real, actionable detail for (code 1301, three known causes); a generic content-rejection or request-failure needs no special mock, since those are just "the create call returns a non-2xx/errors normally" — not a distinct response shape to fixture.

  **The exact error envelope in `account-restricted.json` is an unconfirmed best guess, not a verified real response.** It currently shapes the error as a standard Google API envelope with a `google.rpc.ErrorInfo` in `details[]` and a string `metadata.code: "1301"`. However, the `google.mybusiness.v4` API's own proto definitions describe a `ValidationError` type instead, carrying an `errorDetails[]` array with a numeric `code` field per entry — a materially different shape. Confirm against a real triggered 1301 response (or definitive v4 error-serialization docs) before building the plugin's real error-parsing logic against this fixture.

These mocks will need removing once Woo ships the real Connect Server passthrough for Business Profile, matching the same "swap mock for live" pattern already followed for Search Console and Tag Manager. The `google-gbp` path segment used throughout is a placeholder — Woo's real Connect Server path for Business Profile isn't confirmed yet; expected to be a small string change in `handler.js` once it is.
