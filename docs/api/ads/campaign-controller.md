# [API](../../api.md) | GET /ads/campaigns/`<service>`

This endpoint returns all campaigns associated with an account.

## Response

The response contains an array of campaigns.

## Example Request

```
GET https://domain.test/wp-json/wc/gla/ads/campaigns
```

## Example Response

```javascript
[
    {
        "id": 12124538879,
        "name": "Ad Campaign 2021-01-20",
        "status": "enabled",
        "amount": 10,
        "country": "US"
    },
    {
        "id": 12134267938,
        "name": "Paused campaign",
        "status": "paused",
        "amount": 3.5,
        "country": "US"
    }
]
```

----

# [API](../../api.md) | GET /ads/campaigns/<id>`<service>`

This endpoint returns a single campaign.

## Request

The request URL must contain the following path parameters:

- id: Campaign ID that is being requested

## Response

The response contains details about the requested campaign such as:

- id: Campaign ID
- name: Campaign name
- amount: Campaign budget/amount
- country: Target country

## Example Request

```
GET https://domain.test/wp-json/wc/gla/ads/campaigns/12134267938
```

## Example Response

```javascript
{
    "id": 12134267938,
    "name": "Ad Campaign 2021-01-22",
    "amount": 10,
    "country": "US"
}
```

----


# [API](../../api.md) | POST /ads/campaigns/`<service>`

This endpoint creates a campaign.

## Request

The request must contain details about the campaign to be created e.g.

- name: A name for the campaign
- amount: Campaign budget/amount
- country: Target country for the campaign

## Response

The response contains details about the created campaign such as:

- id: Campaign ID
- name: Campaign name
- amount: Campaign budget/amount
- country: Target country

## Example Request

```
POST https://domain.test/wp-json/wc/gla/ads/campaigns
```

```javascript
{
    "name": "Ad Campaign 2021-01-21",
    "amount": 10.00,
    "country": "US"
}
```

## Example Response

```javascript
{
    "id": 12134267938,
    "name": "Ad Campaign 2021-01-22",
    "amount": 10,
    "country": "US"
}
```

----

# [API](../../api.md) | POST/PUT/PATCH /ads/campaigns/(?P<id>[\d]+)`<service>`

This endpoint edits a campaign.

## Response

The response contains the following fields:

- status: When the request was successful or not e.g. "success"
- message: Additional detail about the response e.g. "Successfully edited campaign."
- id: ID of the edited campaign.

## Example Request

```
PATCH https://domain.test/wp-json/wc/gla/ads/campaigns/12134267938
```

```javascript
{
    "amount": 3.50,
}
```

## Example Response

```javascript
{
    "status": "success",
    "message": "Successfully edited campaign.",
    "id": 12134267938
}
```

----

# [API](../../api.md) | DELETE /ads/campaigns/(?P<id>[\d]+)`<service>`

This endpoint deletes a campaign.

## Response

The response contains the following fields:

- status: When the request was successful or not e.g. "success"
- message: Additional detail about the response e.g. "Successfully deleted campaign."
- id: ID of the deleted campaign.

## Example Request

```
DELETE https://domain.test/wp-json/wc/gla/ads/campaigns/12134267938
```

## Example Response

```javascript
{
    "status": "success",
    "message": "Successfully deleted campaign.",
    "id": 12134267938
}
```
