# [API](../../api.md) | GET /jetpack/connect/

This endpoint returns the URL used to connect to Jetpack/WordPress.com.

## Response

The response contains the following fields:

- url: URL to redirect merchants to to connect to Jetpack/WordPress.com.

## Example Request

```
GET https://domain.test/wp-json/wc/gla/jetpack/connect
```

## Example Response

```javascript
{
    url: https://wordpresscom/?somethings
}
```

----

# [API](../../api.md) | DELETE /jetpack/connect/

This endpoint disconnects/revokes the jetpack connection.

## Response

The response contains the following fields:

- status: Whether the request was successful or not e.g. "success"
- message: Additional detail about the response e.g. "Successfully disconnected."

## Example Request

```
DELETE https://domain.test/wp-json/wc/gla/jetpack/connect
```

## Example Response

```javascript
{
    "status": "success",
    "message": "Successfully disconnected."
}
```

----

# [API](../../api.md) | GET /jetpack/connected/

This endpoint returns xxx.

## Response

The response contains the following fields:

- active: Whether the connection is active
- owner: Whether the current user is the connection owner (yes or no)
- displayName: Account display name
- email: Account email address

## Example Response

```javascript
{
    "active": "connected",
    "owner": "yes",
    "displayName": "Jason",
    "email": "jason@example.com"
}
```
