# [API](../../api.md) | GET /google/connect/`<service>`

This endpoint returns the URL used to connect to Google.

## Response

The response contains the following fields:

- url: URL to redirect merchants to to connect to Google.

## Example Request

```
GET https://domain.test/wp-json/wc/gla/google/connect
```

## Example Response

```javascript
{
    url: https://google.com/?somethings
}
```

----

# [API](../../api.md) | DELETE /google/connect/`<service>`

This endpoint disconnects/revokes the google connection.

## Response

The response contains the following fields:

- status: Whether the request was successful or not e.g. "success"
- message: Additional detail about the response e.g. "Successfully disconnected."

## Example Request

```
DELETE https://domain.test/wp-json/wc/gla/google/connect
```

## Example Response

```javascript
{
    "status": "success",
    "message": "Successfully disconnected."
}
```

----

# [API](../../api.md) | GET /google/connected/`<service>`

This endpoint returns status of the Google account connection indicating when an account is connected or not.

## Response

The response contains the following fields:

- status: Whether the account is connected or disconnected
- email: Account email address or null if disconnected.

## Example Response

```javascript
{
    "status ": "connected",
    "email": "jason@example.com"
}
```

```javascript
{
    "status": "disconnected",
    "email": ""
}
```