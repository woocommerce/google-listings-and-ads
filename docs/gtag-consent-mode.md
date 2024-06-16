## Google Analytics (gtag) Consent Mode

Unless you're running the [Google Analytics for WooCommerce](https://woo.com/products/woocommerce-google-analytics/) extension for a more sophisticated configuration, Google Listings and Ads will add Google's `gtag` to help you track some customer behavior.

To respect your customers' privacy, we set up the default state of [consent mode](https://support.google.com/analytics/answer/9976101). We set it to deny all the parameters for visitors from the EEA region.

You can also customize your own default state configuration using the `woocommerce_gla_gtag_consent` filter providing any snippet that uses [Google's `gtag('consent', 'default', {…})` API ](https://developers.google.com/tag-platform/security/guides/consent?consentmode=advanced).

After the page loads, the consent for particular parameters can be updated by other plugins or custom code implementing UI for customer-facing configuration using [Google's consent API](https://developers.google.com/tag-platform/security/guides/consent?hl=en&consentmode=advanced#update-consent) (`gtag('consent', 'update', {…})`).

## Cookie banners & WP Consent API

The extension does not provide any UI, like a cookie banner, to let your visitors grant consent for tracking. However, it's integrated with [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), so you can pick another extension that provides a banner that meets your needs.

Each of those extensions may require additional setup or registration. Usually, the basic default setup works out of the box, but there may be some integration caveats.