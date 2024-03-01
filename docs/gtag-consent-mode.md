## Googla Analytics (gtag) Consent Mode

Unless you're running the [Google Analytics Integration](https://woo.com/products/woocommerce-google-analytics/) extension for a more sophisticated configuration, Google Listings and Ads will add Google's `gtag` to help you track some customer behavior.

To respect your customers' privacy, we set up the default state of [consent mode](https://support.google.com/analytics/answer/9976101). We set it to deny all the parameters for visitors from the EEA region. You can add an extension or CMP that delivers a banner or any other UI to let visitors update their consent in runtime.

You can also customize your own default state configuration using the `woocommerce_gla_gtag_consent` filter providing any snippet that uses [Google's `gtag('consent', 'default', {…})` API ](https://developers.google.com/tag-platform/security/guides/consent?consentmode=advanced).
