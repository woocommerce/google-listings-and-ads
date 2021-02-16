# [API](../../api.md) | GET /ads/accounts/`<service>`

This endpoint lists a Google users existing ad accounts.

## Response

The response contains an array of Ads accounts ids.

## Example Request

```
GET https://domain.test/wp-json/wc/gla/ads/accounts
```

## Example Response

```javascript
[
    7897423400,
    9898373407,
    7946319897
]
```

----

# [API](../../api.md) | POST /ads/accounts/`<service>`

This endpoint creates a new Ads account or links an existing Ads account if there is an "id" in the request body.

Note: Only if the account is not already linked to our manager account will it actually link the account, in both cases the request will respond with the same successful response of the ID number.

For both the create and link account requests, after it is completed the wp_options table should have an entry with the name `gla_ads_id`.

## Request

To link an account the request body must contain an id.

- id: Ads Account ID to be linked.

## Response

The response contains the following fields:

- id: Ads Account ID that has been created or linked

## Example Request

```
POST https://domain.test/wp-json/wc/gla/ads/accounts
```

```javascript
{
    "id": 5942319812
}
```

## Example Response

```javascript
{
    "id": 5942319812
}
```

----

# [API](../../api.md) | GET /ads/connection/`<service>`

This endpoint gets the connected ads account.

## Response

The response contains the following fields:

- id: The connected ads account id.
- status: The status of the ads account i.e. "connected"

## Example Request

```
GET https://domain.test/wp-json/wc/gla/ads/connection
```


## Example Response

```javascript
{
    "id": 1234567890,
    "status": "connected"
}
```

----

# [API](../../api.md) | DELETE /ads/connection/`<service>`

This endpoint disconnects the connected ads account.

## Response

The response contains the following fields:

- status: When the request was successful or not e.g. "success"
- message: Additional detail about the response e.g. "Successfully disconnected."

## Example Request

```
POST https://domain.test/wp-json/wc/gla/ads/connection
```

## Example Response

```javascript
{
    "status": "success",
    "message": "Successfully disconnected."
}
```
