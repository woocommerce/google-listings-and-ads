# WPCOMProxy

## `/settings/google-for-woocommerce` endpoint schema.

Following is the desired and expected structure of `/settings/google-for-woocommerce?gla_syncable=1` response.

```js
[
    {
        "id": "gla_plugin_version",
        "label": "Google for WooCommerce: Current plugin version",
        "value": WC_GLA_VERSION,
    },
    {
        "id": "gla_google_connected",
        "label": "Google for WooCommerce: Is Google account connected?",
        "value": ( true | false )
    },
    {
        "id": "gla_language",
        "label": "Google for WooCommerce: Store language",
        "value": LANGUAGE_CODE,
    },
    {
        "id": "gla_merchant_center",
        "label": "Google for WooCommerce: Merchant Center settings",
        "value": ( {
            "shipping_rate": ( "flat" | "manual" | "automatic" ),
            "shipping_time": ( "flat" | "manual" ),
            "tax_rate": ( "destination" | "manual" )
        } | null ) // `null` if the user has not set it up yet, or disconnected accounts.
    },
    {
        "id": "gla_shipping_rates",
        "label": "Google for WooCommerce: Shipping Rates",
        "value": {
            [ COUNTRY_CODE_1 ]: {
                "country_code": COUNTRY_CODE_1,
                "currency": CURRENCY_CODE_1,
                "free_shipping_threshold": ( FREE_SHIPPING_THRESHOLD_1 | null ),
                "rate": AMOUNT_1,
            },
            // …
        }
    },
    {
        "id": "gla_shipping_times",
        "label": "Google for WooCommerce: Shipping Times",
        "value": {
            [ COUNTRY_CODE_1 ]: {
                "country_code": COUNTRY_CODE_1,
                "time": TIME,
                "max_time": MAX_TIME
            },
            // …
        }
    },
    {
        "id": "gla_target_audience",
        "label": "Google for WooCommerce: Target Audience",
        "value": ( {
            "location": ( "selected" | "all" ),
            "countries": [
                COUNTRY_CODE_1,
                COUNTRY_CODE_2,
                // …
            ]
        } | null ) // `null` if the user has not set it up yet, or disconnected accounts.
    }
]
```