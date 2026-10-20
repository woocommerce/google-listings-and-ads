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
- `unverified_location` will report every Google Business Profile location as unverified when reading its verification state

```
PROXY_MODE=<access_error|delete_error|update_error|account_restricted|unverified_location> npm run test-proxy
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

At the moment the report data and the Google Business Profile access grant are mocked, along with `localPosts.patch` and a Business Profile account-restriction error. The rest of Business Profile's traffic — account/location listing and the `localPosts` create/get/delete operations — is confirmed live and goes straight to the real Connect Server.

### Google Business Profile — real Connect Server hosts

Business Profile is split across four separate hosts, not one shared path — each operation below routes through its own prefix:

| WCS prefix | Real upstream host |
|---|---|
| `google/google-gbp-account-management` | `mybusinessaccountmanagement.googleapis.com` (v1) |
| `google/google-gbp-business-information` | `mybusinessbusinessinformation.googleapis.com` (v1) |
| `google/google-gbp-verifications` | `mybusinessverifications.googleapis.com` (v1) |
| `google/google-gbp/v4` | `mybusiness.googleapis.com` (v4 — legacy Local Posts) |

### Google Business Profile — full endpoint inventory

**v1 (Account Management, Business Information, Verifications APIs):**

| Operation | Used by | Mocked? |
|---|---|---|
| `accounts.list` | Selecting an eligible location | No — confirmed live, falls through |
| `accounts.locations.list` | Selecting an eligible location | No — confirmed live, falls through |
| `locations.getVoiceOfMerchantState` | Storing a shared post as pending verification when its location is unverified | Yes — three selectable states (see below). Confirmed live, but stays mocked so those states stay reachable |

**Granting access is mocked.** The live Connect Server doesn't accept the `business.manage` scope yet. When the plugin asks the shared connection (`google/connection/google-mc`) for that scope, the proxy skips Google's consent screen. It returns an `oauthUrl` that leads straight back to the plugin with `google-mc=connected`, as if the merchant approved. After that, the shared connection's status response, still fetched from the real Connect Server, lists `business.manage` among the granted scopes. The proxy keeps this in memory only, so restarting it removes the grant. Requests for any other scope go to the real Connect Server unchanged.

Disconnecting needs no mock, because it only clears the plugin's own stored location.

**v4 (legacy My Business API, local posts):**

| Operation | Used by | Mocked? |
|---|---|---|
| `localPosts.create` | Publishing a post (from either editor), including sync failure handling | No — confirmed live, falls through. The account-restriction failure mode stays mocked via `PROXY_MODE` (see below) |
| `localPosts.get` | Not used anywhere in the plugin, by deliberate architecture (no proactive read-back) | **Not mocked** — no fixture exists; falls through if ever called |
| `localPosts.patch` | Updating a previously synced post | Yes — no caller yet (the update-sync feature isn't built) |
| `localPosts.delete` | Deleting a synced post | No — confirmed live, falls through |
| `localPosts.list` | Not used by any current feature's behavior — only useful for ad hoc inspection of what actually exists on a location | **Deliberately not mocked** — falls through to the real Connect Server |

**Selectable states:**

- Three fixed location IDs select the three eligibility states `getVoiceOfMerchantState` can return: `locations/1111` (eligible, the default for any other ID), `locations/2222` (unverified), `locations/3333` (suspended). `LOCATION_DISABLED_FOR_LOCAL_POST_API` — once a fourth candidate state — is not mocked; Google confirmed the code is deprecated and won't occur.

  **`accounts.locations.list` falls through to the real Connect Server**, so a location selected through the plugin's own UI always has a real ID, never `2222` or `3333` — every real location hits the `eligible` default. To reach the `unverified` or `suspended` states, call `getVoiceOfMerchantState` directly against the proxy with one of those IDs (e.g. via curl), rather than through the plugin's own flow. To see a publish stored as pending verification, run the proxy with `PROXY_MODE=unverified_location`, then publish a shared post. That mode reports the real connected location as unverified, while `localPosts.create` still goes to the real Connect Server and succeeds.
- `PROXY_MODE=account_restricted` makes `localPosts.create` return an account-restriction error instead of succeeding — see Modes above. This is the one failure category Google gave real, actionable detail for (code 1301, three known causes); a generic content-rejection or request-failure needs no special mock, since those are just "the create call returns a non-2xx/errors normally" — not a distinct response shape to fixture.

  **The exact error envelope in `account-restricted.json` is an unconfirmed best guess, not a verified real response.** It currently shapes the error as a standard Google API envelope with a `google.rpc.ErrorInfo` in `details[]` and a string `metadata.code: "1301"`. However, the `google.mybusiness.v4` API's own proto definitions describe a `ValidationError` type instead, carrying an `errorDetails[]` array with a numeric `code` field per entry — a materially different shape. Confirm against a real triggered 1301 response (or definitive v4 error-serialization docs) before building the plugin's real error-parsing logic against this fixture. This is why the mock stays in place despite the host it lives on being otherwise confirmed live.

`localPosts.patch` stays mocked until the update-sync feature is built, and `locations.getVoiceOfMerchantState` stays mocked because a real location can't be switched between the three states on demand. Removing either mock would cost its feature a local dev/demo path, even though both hosts are already confirmed live.
