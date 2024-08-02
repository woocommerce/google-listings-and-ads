# Usage Tracking

_Google for WooCommerce_ implements usage tracking, based on the native [WooCommerce Usage Tracking](https://woocommerce.com/usage-tracking/), and is only enabled when WooCommerce Tracking is enabled.

When a store opts in to WooCommerce usage tracking and uses _Google for WooCommerce_, they will also be opted in to the tracking added by _Google for WooCommerce_.

## What is tracked

As in WooCommerce core, only non-sensitive data about how a store is set up and managed is tracked. We **do not track or store personal data** from your clients.

-   Plugin version
-   Settings
    -   WordPress.com account connection status
    -   Google Merchant Center account connection status and connected ID
    -   Google Ads account connected ID

<!-- TODO: add more tracking information -->

### Tracking events

All event names are prefixed by `wcadmin_`.

### Global event properties

Most events have the following properties:

- `gla_version`: Plugin version
- `gla_mc_id`: Google Merchant Center account ID if connected
- `gla_ads_id`: Google Ads account ID if connected

### `gla_activated_from_source`
Plugin is activated from the "Add Plugins" page in the admin, and has `utm` query parameters indicating deep linking. Parameters currently tracked (and sent as properties):
 - `utm_source`
 - `utm_medium`
 - `utm_campaign`
 - `utm_term`
 - `utm_content` 

### `gla_mc_account_reclaim_url_agreement_check`
Clicking on the checkbox to agree with the implications of reclaiming URL. 
#### Properties
|   |   |   |
|---|---|---|
`checked` |  | indicate whether the checkbox is checked or unchecked.

### `gla_mc_url_switch`
Clicking on the checkbox to agree with the implications of reclaiming URL. 
#### Properties
|   |   |   |
|---|---|---|
`action` | `string` | <ul><li>`required`: the Merchant Center account has a different, claimed URL and needs to be changed. <li>`success`: the Merchant Center account has been changed from blank, updated from a different, unclaimed URL, or after user confirmation of a required change.</ul>

### `gla_site_claim`
#### Properties
|   |   |   |
|---|---|---|
`action` | `string` | <ul><li>`overwrite_required`: the site URL is claimed by another Merchant Center account and overwrite confirmation is required <li>`success`: URL has been successfully set or overwritten.<li>`failure`</ul>
`details` | `string` | Used for `failure` action. <ul><li>`independent_account`: unable to execute site claim because the provided Merchant Center account is not a sub-account of our MCA <li>`google_proxy`: claim failed using the user creds (in the `Merchant` class) <li>`google_manager`: claimed failed using MCA creds (paradoxically in the `Middleware` class)</ul>

### `gla_site_verify_failure`
When a site verification with Google fails
#### Properties
|   |   |   |
|---|---|---|
`step` | `string` | the step of the process that failed (token, meta-tag, unknown)

### `gla_site_verify_success`
When a site is successfully verified with Google

### `gla_created_campaign`
When a campaign has been successfully created.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `int` | Campaign ID.
`status` | `string` | Campaign status, `enabled` or `paused`.
`name` | `string` | Campaign name, generated based on date.
`amount` | `float` | Campaign budget.
`country` | `string` | Base target country code.
`targeted_locations` | `string` | Additional target country codes.

### `gla_edited_campaign`
When a campaign has been successfully edited.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `int` | Campaign ID.
`status` | `string` | Campaign status, `enabled` or `paused`.
`name` | `string` | Campaign name.
`amount` | `float` | Campaign budget.

### `gla_deleted_campaign`
When a campaign has been successfully deleted.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `int` | Campaign ID.

### `gla_ads_setup_completed`
Ads onboarding has been successfully completed.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`campaign_count` | `int` | Number of campaigns for the connected Ads account.

### `gla_mc_setup_completed`
Merchant Center onboarding has been successfully completed.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`shipping_rate` | `string` | Shipping rate setup `automatic`, `manual`, `flat`.
`offers_free_shipping` | `bool` | Free Shipping is available.
`free_shipping_threshold` | `float` | Minimum amount to avail of free shipping.
`shipping_time` | `string` | Shipping time setup `flat`, `manual`.
`tax_rate` | `string` | Tax rate setup `destination`, `manual`.
`target_countries` | `string` | List of target countries or `all`.

<!-- -- >
## Developer Info
All new tracking info should be updated in this readme.

New snapshot data for **WC Tracker** should be hooked into `Tracking\Events\TrackerSnapshot::include_snapshot_data()`.

New **Tracks** events should be created in `Tracking\Events\Events` (extending `Tracking\Events\BaseEvent`), and need to be registered in `Tracking\Events\EventTracking::$events`. They should also be registered in the `Internal\DependencyManagement\CoreServiceProvider` class:

```php
$this->conditionally_share_with_tags( Loaded::class );
```

 /Dev Info -->

<woocommerce-grow-tracking-jsdoc>
<!---
Everything below will be automatically generated by `woocommerce-grow-tracking-jsdoc`.
Do not edit it manually!
-->

### [`gla_add_paid_campaign_clicked`](../../js/src/components/paid-ads/add-paid-campaign-button.js#L15)
"Add paid campaign" button is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicate the place where the button is located.
`href` | `string` | Indicate the destination where the users is directed to, e.g. `'/google/setup-ads'`.
#### Emitters
- [`AddPaidCampaignButton`](../../js/src/components/paid-ads/add-paid-campaign-button.js#L46) with given props, when clicked.

### [`gla_ads_account_connect_button_click`](../../js/src/components/google-ads-account-card/connect-ads/index.js#L27)
Clicking on the button to connect an existing Google Ads account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `number` | The account ID to be connected.
`context` | `string` | Indicates the place where the button is located.
`step` | `string` | Indicates the step in the onboarding process.
#### Emitters
- [`ConnectAds`](../../js/src/components/google-ads-account-card/connect-ads/index.js#L42) when "Connect" button is clicked.

### [`gla_ads_account_create_button_click`](../../js/src/components/google-ads-account-card/terms-modal/index.js#L18)
Clicking on the button to create a new Google Ads account, after agreeing to the terms and conditions.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates the place where the button is located.
`step` | `string` | Indicates the step in the onboarding process.
#### Emitters
- [`TermsModal`](../../js/src/components/google-ads-account-card/terms-modal/index.js#L36) When agreed by clicking "Create account".

### [`gla_ads_account_disconnect_button_click`](../../js/src/components/google-ads-account-card/disconnect-account.js#L15)
Clicking on the button to disconnect the Google Ads account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates the place where the button is located.
`step` | `string` | Indicates the step in the onboarding process.
#### Emitters
- [`DisconnectAccount`](../../js/src/components/google-ads-account-card/disconnect-account.js#L28) When the user clicks on the button to disconnect the Google Ads account.

### [`gla_ads_set_up_billing_click`](../../js/src/components/paid-ads/billing-card/billing-setup-card.js#L22)
"Set up billing" button for Google Ads account is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`link_id` | `string` | A unique ID for the button within the context, e.g. `set-up-billing`.
`href` | `string` | Indicates the destination where the users is directed to.
`context` | `string` | Indicates the place where the button is located, e.g. `setup-mc` or `setup-ads`.
`step` | `string` | Indicates the step in the onboarding process.
#### Emitters
- [`BillingSetupCard`](../../js/src/components/paid-ads/billing-card/billing-setup-card.js#L39) When the user clicks on the button to set up billing in Google Ads.

### [`gla_attribute_mapping_create_rule`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L32)
Creates the rule successfully
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates where this event happened
#### Emitters
- [`AttributeMappingRuleModal`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L95) When the rule is successfully created  with `{ context: 'attribute-mapping-create-rule-modal' }`

### [`gla_attribute_mapping_delete_rule`](../../js/src/attribute-mapping/attribute-mapping-delete-rule-modal.js#L16)
Deletes the rule successfully
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates where this event happened
#### Emitters
- [`AttributeMappingDeleteRuleModal`](../../js/src/attribute-mapping/attribute-mapping-delete-rule-modal.js#L39) When the rule is successfully deleted with `{ context: 'attribute-mapping-delete-rule-modal'}`

### [`gla_attribute_mapping_delete_rule_click`](../../js/src/attribute-mapping/attribute-mapping-delete-rule-modal.js#L23)
Clicks on delete rule button
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates where this event happened
#### Emitters
- [`AttributeMappingDeleteRuleModal`](../../js/src/attribute-mapping/attribute-mapping-delete-rule-modal.js#L39) When user clicks on delete rule button with `{ context: 'attribute-mapping-delete-rule-modal' }`

### [`gla_attribute_mapping_save_rule_click`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L39)
Clicks on save rule button
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates where this event happened
#### Emitters
- [`AttributeMappingRuleModal`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L95) When user clicks on save rule button  with `{ context: 'attribute-mapping-manage-rule-modal' | 'attribute-mapping-create-rule-modal' }`

### [`gla_attribute_mapping_update_rule`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L25)
Updates the rule successfully
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates where this event happened
#### Emitters
- [`AttributeMappingRuleModal`](../../js/src/attribute-mapping/attribute-mapping-rule-modal.js#L95) When the rule is successfully updated  with `{ context: 'attribute-mapping-manage-rule-modal' }`

### [`gla_bulk_edit_click`](../../js/src/product-feed/product-feed-table-card/index.js#L40)
Triggered when the product feed "bulk edit" functionality is being used
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Name of the table
`number_of_items` | `number` | Edit how many items
`visibility_to` | `string` | `("sync_and_show" \| "dont_sync_and_show")`
#### Emitters
- [`ProductFeedTableCard`](../../js/src/product-feed/product-feed-table-card/index.js#L65) with `context: 'product-feed'`

### [`gla_ces_feedback`](../../js/src/components/customer-effort-score-prompt/index.js#L30)
CES feedback recorded
#### Emitters
- [`CustomerEffortScorePrompt`](../../js/src/components/customer-effort-score-prompt/index.js#L55) whenever the CES feedback is recorded

### [`gla_ces_modal_open`](../../js/src/components/customer-effort-score-prompt/index.js#L25)
CES modal open
#### Emitters
- [`CustomerEffortScorePrompt`](../../js/src/components/customer-effort-score-prompt/index.js#L55) whenever the CES modal is open

### [`gla_ces_snackbar_closed`](../../js/src/components/customer-effort-score-prompt/index.js#L20)
CES prompt snackbar closed
#### Emitters
- [`CustomerEffortScorePrompt`](../../js/src/components/customer-effort-score-prompt/index.js#L55) whenever the CES snackbar (notice) is closed

### [`gla_ces_snackbar_open`](../../js/src/components/customer-effort-score-prompt/index.js#L15)
CES prompt snackbar open
#### Emitters
- [`CustomerEffortScorePrompt`](../../js/src/components/customer-effort-score-prompt/index.js#L55) whenever the CES snackbar (notice) is open

### [`gla_chart_tab_click`](../../js/src/reports/summary-section.js#L20)
Triggered when a chart tab is clicked
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`report` | `string` | Name of the report (e.g. `"reports-programs" \| "reports-products"`)
`context` | `string` | Metric key of the clicked tab (e.g. `"sales" \| "conversions" \| "clicks" \| "impressions" \| "spend"`).
#### Emitters
- [`SummarySection`](../../js/src/reports/summary-section.js#L39)

### [`gla_contact_information_save_button_click`](../../js/src/settings/edit-store-address.js#L28)
Triggered when the save button in contact information page is clicked.
#### Emitters
- [`EditStoreAddress`](../../js/src/settings/edit-store-address.js#L41)

### [`gla_dashboard_edit_program_click`](../../js/src/dashboard/all-programs-table-card/edit-program-button/edit-program-prompt-modal/index.js#L17)
Triggered when "continue" to edit program button is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`programId` | `string` | program id
`url` | `string` | url (free or paid)
#### Emitters
- [`EditProgramPromptModal`](../../js/src/dashboard/all-programs-table-card/edit-program-button/edit-program-prompt-modal/index.js#L32) when "Continue to edit" is clicked.

### [`gla_datepicker_update`](../../js/src/utils/tracks.js#L135)
Triggered when datepicker (date ranger picker) is updated,
 with report name and data that comes from `DateRangeFilterPicker`'s `onRangeSelect` callback
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`report` | `string` | Name of the report (e.g. `"dashboard" \| "reports-programs" \| "reports-products" \| "product-feed"`)
`compare` | `string` | Value selected in datepicker.
`period` | `string` | Value selected in datepicker.
`before` | `string` | Value selected in datepicker.
`after` | `string` | Value selected in datepicker.
#### Emitters
- [`AppDateRangeFilterPicker`](../../js/src/dashboard/app-date-range-filter-picker/index.js#L27) with `report: props.trackEventReportId` and `data` given by `DateRangeFilterPicker`'s `onRangeSelect` callback.
- [`ProductsReportFilters`](../../js/src/reports/products/products-report-filters.js#L41)
- [`ProgramsReportFilters`](../../js/src/reports/programs/programs-report-filters.js#L43)

### [`gla_disconnected_accounts`](../../js/src/settings/linked-accounts.js#L32)
Accounts are disconnected from the Setting page
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | (`all-accounts`\|`ads-account`) - indicate which accounts have been disconnected.

### [`gla_documentation_link_click`](../../js/src/components/app-documentation-link/index.js#L6)
When a documentation link is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`link_id` | `string` | link identifier
`context` | `string` | indicate which link is clicked
`href` | `string` | link's URL
#### Emitters
- [`AppDocumentationLink`](../../js/src/components/app-documentation-link/index.js#L29)
- [`ChooseAudienceSection`](../../js/src/components/free-listings/choose-audience-section/choose-audience-section.js#L29) with `{ context: 'setup-mc-audience', link_id: 'site-language', href: 'https://support.google.com/merchants/answer/160637' }`
- [`ConnectAds`](../../js/src/components/google-ads-account-card/connect-ads/index.js#L42) with `{ context: 'setup-ads-connect-account', link_id: 'connect-sub-account', href: 'https://support.google.com/google-ads/answer/6139186' }`
- [`ConnectGoogleAccountCard`](../../js/src/components/google-account-card/connect-google-account-card.js#L23) with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#general-requirements' }`
- [`ContactInformation`](../../js/src/components/contact-information/index.js#L91)
	- with `{ context: 'setup-mc-contact-information', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
	- with `{ context: 'settings-no-phone-number-notice', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
	- with `{ context: 'settings-no-store-address-notice', link_id: 'contact-information-read-more', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information' }`
- [`DifferentCurrencyNotice`](../../js/src/components/different-currency-notice.js#L28)
	- with `{ context: "dashboard", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
	- with `{ context: "reports-products", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
	- with `{ context: "reports-programs", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
- [`EditPhoneNumber`](../../js/src/settings/edit-phone-number.js#L29) with `{ context: "settings-phone-number", link_id: "contact-information-read-more", href: "https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information" }`
- [`EditStoreAddress`](../../js/src/settings/edit-store-address.js#L41) with `{ context: "settings-store-address", link_id: "contact-information-read-more", href: "https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#contact-information" }`
- [`Faqs`](../../js/src/get-started-page/faqs/index.js#L276)
	- with `{ context: 'faqs', linkId: 'general-requirements', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#general-requirements' }`.
	- with `{ context: 'faqs', linkId: 'claiming-urls', href: 'https://support.google.com/merchants/answer/7527436' }`.
	- with `{ context: 'faqs', linkId: 'google-merchant-center-requirements', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#google-merchant-center-requirements' }`.
	- with `{ context: 'faqs', linkId: 'performance-max', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns' }`.
	- with `{ context: 'faqs', linkId: 'free-listings', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/product-feed-information-and-free-listings/#section-1' }`.
	- with `{ context: 'faqs', linkId: 'campaign-analytics', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/campaign-analytics' }`.
	- with `{ context: 'faqs', linkId: 'terms-and-conditions-of-google-ads-coupons', href: 'https://www.google.com/ads/coupons/terms/' }`.
- [`Faqs`](../../js/src/setup-mc/setup-stepper/setup-accounts/faqs.js#L68) with `{ context: 'faqs', link_id: 'find-a-partner', href: 'https://comparisonshoppingpartners.withgoogle.com/find_a_partner/' }`
- [`FaqsSection`](../../js/src/components/paid-ads/asset-group/faqs-section.js#L73) with `{ context: 'assets-faq', linkId: 'assets-faq-about-ad-formats-available-in-different-campaign-types', href: 'https://support.google.com/google-ads/answer/1722124' }`.
- [`FreeAdCredit`](../../js/src/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.js#L27) with `{ context: 'setup-ads', link_id: 'free-ad-credit-terms', href: 'https://www.google.com/ads/coupons/terms/' }`
- [`GetStartedCard`](../../js/src/get-started-page/get-started-card/index.js#L23) with `{ context: 'get-started', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
- [`GetStartedWithVideoCard`](../../js/src/get-started-page/get-started-with-video-card/index.js#L23) with `{ context: 'get-started-with-video', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
- [`GoogleMCDisclaimer`](../../js/src/setup-mc/setup-stepper/setup-accounts/index.js#L36)
	- with `{ context: 'setup-mc-accounts', link_id: 'comparison-shopping-services', href: 'https://support.google.com/merchants/topic/9080307' }`
	- with `{ context: 'setup-mc-accounts', link_id: 'comparison-shopping-partners-find-a-partner', href: 'https://comparisonshoppingpartners.withgoogle.com/find_a_partner/' }`
- [`IssuesTableDataModal`](../../js/src/product-feed/issues-table-card/issues-table-data-modal.js#L21) with { context: 'issues-data-table-modal' }
- [`ProductStatusHelpPopover`](../../js/src/product-feed/product-statistics/product-status-help-popover/index.js#L16) with `{ context: 'product-feed', link_id: 'product-sync-statuses', href: 'https://support.google.com/merchants/answer/160491' }`
- [`ReclaimUrlCard`](../../js/src/components/google-mc-account-card/reclaim-url-card/index.js#L41) with `{ context: 'setup-mc', link_id: 'claim-url', href: 'https://support.google.com/merchants/answer/176793' }`
- [`RequestFullAccessGoogleAccountCard`](../../js/src/components/google-account-card/request-full-access-google-account-card.js#L26) with `{ context: 'setup-mc-accounts', link_id: 'required-google-permissions', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#general-requirements' }`
- [`ShippingRateSection`](../../js/src/components/shipping-rate-section/shipping-rate-section.js#L23)
	- with `{ context: 'setup-mc-shipping', link_id: 'shipping-read-more', href: 'https://support.google.com/merchants/answer/7050921' }`
	- with `{ context: 'setup-mc-shipping', link_id: 'shipping-manual', href: 'https://www.google.com/retail/solutions/merchant-center/' }`
- [`ShippingTimeSection`](../../js/src/components/free-listings/configure-product-listings/shipping-time-section.js#L17) with `{ context: 'setup-mc-shipping', link_id: 'shipping-read-more', href: 'https://support.google.com/merchants/answer/7050921' }`
- [`TaxRate`](../../js/src/components/free-listings/configure-product-listings/tax-rate.js#L22)
	- with `{ context: 'setup-mc-tax-rate', link_id: 'tax-rate-read-more', href: 'https://support.google.com/merchants/answer/160162' }`
	- with `{ context: 'setup-mc-tax-rate', link_id: 'tax-rate-manual', href: 'https://www.google.com/retail/solutions/merchant-center/' }`
- [`TermsModal`](../../js/src/components/google-ads-account-card/terms-modal/index.js#L36)
	- with `{ context: 'setup-ads', link_id: 'shopping-ads-policies', href: 'https://support.google.com/merchants/answer/6149970' }`
	- with `{ context: 'setup-ads', link_id: 'google-ads-terms-of-service', href: 'https://support.google.com/adspolicy/answer/54818' }`
- [`TermsModal`](../../js/src/components/google-mc-account-card/terms-modal/index.js#L29) with `{ context: 'setup-mc', link_id: 'google-mc-terms-of-service', href: 'https://support.google.com/merchants/answer/160173' }`
- [`UnsupportedCountry`](../../js/src/get-started-page/unsupported-notices/index.js#L73) with `{ context: "get-started", link_id: "supported-countries" }`
- [`UnsupportedLanguage`](../../js/src/get-started-page/unsupported-notices/index.js#L30) with `{ context: 'get-started', link_id: 'supported-languages', href: 'https://support.google.com/merchants/answer/160637' }`
- [`exports`](../../js/src/components/paid-ads/ads-campaign.js#L38) with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`

### [`gla_edit_mc_phone_number`](../../js/src/components/contact-information/phone-number-card/phone-number-card-preview.js#L14)
Triggered when phone number edit button is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`path` | `string` | The path used in the page, e.g. `"/google/settings"`.
`subpath` | `string` | The subpath used in the page, or `undefined` when there is no subpath.
#### Emitters
- [`PhoneNumberCardPreview`](../../js/src/components/contact-information/phone-number-card/phone-number-card-preview.js#L33) Whenever "Edit" is clicked.

### [`gla_edit_mc_store_address`](../../js/src/components/contact-information/store-address-card.js#L172)
Trigger when store address edit button is clicked.
 Before `1.5.0` this name was used for tracking clicking "Edit in settings" to edit the WC address. As of `>1.5.0`, that event is now tracked as `edit_wc_store_address`.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`path` | `string` | The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
`subpath` | `string\|undefined` | The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
#### Emitters
- [`StoreAddressCardPreview`](../../js/src/components/contact-information/store-address-card.js#L192) Whenever "Edit" is clicked.

### [`gla_edit_product_click`](../../js/src/product-feed/product-feed-table-card/index.js#L49)
Triggered when edit links are clicked from product feed table.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`status` | `string` | `("approved" \| "partially_approved" \| "expiring" \| "pending" \| "disapproved" \| "not_synced")`
`visibility` | `string` | `("sync_and_show" \| "dont_sync_and_show")`
#### Emitters
- [`ProductFeedTableCard`](../../js/src/product-feed/product-feed-table-card/index.js#L65)

### [`gla_edit_product_issue_click`](../../js/src/product-feed/issues-table-card/index.js#L43)
Triggered when edit links are clicked from Issues to resolve table.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`code` | `string` | Issue code returned from Google
`issue` | `string` | Issue description returned from Google

### [`gla_edit_wc_store_address`](../../js/src/components/contact-information/store-address-card.js#L26)
Triggered when store address "Edit in WooCommerce Settings" button is clicked.
 Before `1.5.0` it was called `edit_mc_store_address`.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`path` | `string` | The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
`subpath` | `string\|undefined` | The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
#### Emitters
- [`StoreAddressCard`](../../js/src/components/contact-information/store-address-card.js#L56) Whenever "Edit in WooCommerce Settings" button is clicked.

### [`gla_faq`](../../js/src/components/faqs-panel/index.js#L22)
Clicking on faq item to collapse or expand it.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `string` | FAQ identifier
`action` | `string` | (`expand`\|`collapse`)
`context` | `string` | Indicates which page / module the FAQ is in
#### Emitters
- [`Faqs`](../../js/src/get-started-page/faqs/index.js#L276)
	- with `{ context: 'get-started', id: 'what-do-i-need-to-get-started', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'what-do-i-need-to-get-started', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'what-if-i-already-have-free-listings', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'what-if-i-already-have-free-listings', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'is-my-store-ready-to-sync-with-google', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'is-my-store-ready-to-sync-with-google', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'what-is-a-performance-max-campaign', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'what-is-a-performance-max-campaign', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'what-are-free-listings', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'what-are-free-listings', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'where-to-track-free-listings-and-performance-max-campaign-performance', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'where-to-track-free-listings-and-performance-max-campaign-performance', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'how-to-sync-products-to-google-free-listings', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'how-to-sync-products-to-google-free-listings', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'can-i-run-both-shopping-ads-and-free-listings-campaigns', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'can-i-run-both-shopping-ads-and-free-listings-campaigns', action: 'collapse' }`.
	- with `{ context: 'get-started', id: 'how-can-i-get-the-ad-credit-offer', action: 'expand' }`.
	- with `{ context: 'get-started', id: 'how-can-i-get-the-ad-credit-offer', action: 'collapse' }`.
- [`Faqs`](../../js/src/setup-mc/setup-stepper/setup-accounts/faqs.js#L68)
	- with `{ context: 'setup-mc-accounts', id: 'why-do-i-need-a-wp-account', action: 'expand' }`.
	- with `{ context: 'setup-mc-accounts', id: 'why-do-i-need-a-wp-account', action: 'collapse' }`.
	- with `{ context: 'setup-mc-accounts', id: 'why-do-i-need-a-google-mc-account', action: 'expand' }`.
	- with `{ context: 'setup-mc-accounts', id: 'why-do-i-need-a-google-mc-account', action: 'collapse' }`.
- [`FaqsSection`](../../js/src/components/paid-ads/asset-group/faqs-section.js#L73)
	- with `{ context: 'campaign-management', id: 'what-will-my-ads-look-like', action: 'expand' | 'collapse' }`.
	- with `{ context: 'campaign-management', id: 'what-makes-these-ads-different-from-product-ads', action: 'expand' | 'collapse' }`.

### [`gla_filter`](../../js/src/utils/tracks.js#L147)
Triggered when changing products & variations filter,
 with data that comes from
 `FilterPicker`'s `onFilterSelect` callback.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`report` | `string` | Name of the report (e.g. `"reports-products"`)
`filter` | `string` | Value of the filter (e.g. `"all" \| "single-product" \| "compare-products"`)
`variationFilter` | `string \| undefined` | Value of the variation filter (e.g. `undefined \| "single-variation" \| "compare-variations"`)
#### Emitters
- [`ProductsReportFilters`](../../js/src/reports/products/products-report-filters.js#L41)
- [`ProgramsReportFilters`](../../js/src/reports/programs/programs-report-filters.js#L43)

### [`gla_free_ad_credit_country_click`](../../js/src/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.js#L16)
Clicking on the link to view free ad credit value by country.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which page the link is in.
#### Emitters
- [`FreeAdCredit`](../../js/src/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.js#L27) with `{ context: 'setup-ads' }`.

### [`gla_free_campaign_edited`](../../js/src/edit-free-campaign/index.js#L30)
Saving changes to the free campaign.
#### Emitters
- [`EditFreeCampaign`](../../js/src/edit-free-campaign/index.js#L46)

### [`gla_google_account_connect_button_click`](../../js/src/utils/tracks.js#L175)
Clicking on the button to connect Google account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | (`setup-mc`\|`setup-ads`\|`reconnect`) - indicate the button is clicked from which page.
`action` | `string` | (`authorization`\|`scope`) 	- `authorization` is used when the plugin has not been authorized yet and requests Google account access and permission scopes from users.   - `scope` is used when requesting required permission scopes from users in order to proceed with more plugin functions. Added with the Partial OAuth feature (aka Incremental Authorization).
#### Emitters
- [`AuthorizeAds`](../../js/src/components/google-ads-account-card/authorize-ads.js#L20) with `{ action: 'scope', context: 'setup-ads' }`
- [`ConnectGoogleAccountCard`](../../js/src/components/google-account-card/connect-google-account-card.js#L23)
	- with `{ action: 'authorization', context: 'reconnect' }`
	- with `{ action: 'authorization', context: 'setup-mc' }`
- [`RequestFullAccessGoogleAccountCard`](../../js/src/components/google-account-card/request-full-access-google-account-card.js#L26)
	- with `{ action: 'scope', context: 'reconnect' }`
	- with `{ action: 'scope', context: 'setup-mc' }`

### [`gla_google_account_connect_different_account_button_click`](../../js/src/components/google-account-card/connected-google-account-card.js#L15)
Clicking on the "connect to a different Google account" button.
#### Emitters
- [`ConnectedGoogleAccountCard`](../../js/src/components/google-account-card/connected-google-account-card.js#L32)

### [`gla_google_ads_account_link_click`](../../js/src/setup-ads/ads-stepper/setup-billing/billing-saved-card/index.js#L19)
Clicking on a Google Ads account text link.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which page / module the link is in
`href` | `string` | Where the user is redirected
`link_id` | `string` | A unique ID for the link within the page / module
#### Emitters
- [`BillingSavedCard`](../../js/src/setup-ads/ads-stepper/setup-billing/billing-saved-card/index.js#L31) with `{ context: 'setup-ads', link_id: 'google-ads-account' }`

### [`gla_google_mc_link_click`](../../js/src/utils/tracks.js#L185)
Clicking on a Google Merchant Center link.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which page / module the link is in
`href` | `string` | Link's URL
#### Emitters
- [`FreePerformanceCard`](../../js/src/dashboard/summary-section/index.js#L22) with `{ context: 'dashboard' }`
- [`MetricNumber`](../../js/src/reports/metric-number.js#L42) with `{ context: 'reports' }`

### [`gla_help_click`](../../js/src/components/help-icon-button.js#L12)
"Help" button is clicked.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates the place where the button is located, e.g. `setup-ads`.
#### Emitters
- [`HelpIconButton`](../../js/src/components/help-icon-button.js#L30)

### [`gla_import_assets_by_final_url_button_click`](../../js/src/components/paid-ads/asset-group/assets-loader.js#L80)
Clicking on the "Scan for assets" button.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`type` | `string` | The type of the selected Final URL suggestion to be imported. Possible values: `post`, `term`, `homepage`.
#### Emitters
- [`exports`](../../js/src/components/paid-ads/asset-group/assets-loader.js#L96)

### [`gla_launch_paid_campaign_button_click`](../../js/src/utils/tracks.js#L167)
Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign in the Google Ads setup flow.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`audiences` | `string` | Country codes of the paid campaign audience countries, e.g. `'US,JP,AU'`. This means the campaign is created with the multi-country targeting feature. Before this feature support, it was implemented as 'audience'.
`budget` | `string` | Daily average cost of the paid campaign
#### Emitters
- [`SetupAdsForm`](../../js/src/setup-ads/setup-ads-form.js#L24) on submit

### [`gla_mc_account_connect_button_click`](../../js/src/components/google-mc-account-card/connect-mc/index.js#L25)
Clicking on the button to connect an existing Google Merchant Center account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `number` | The account ID to be connected.
#### Emitters
- [`ConnectMC`](../../js/src/components/google-mc-account-card/connect-mc/index.js#L42)

### [`gla_mc_account_connect_different_account_button_click`](../../js/src/components/google-mc-account-card/connected-google-mc-account-card.js#L29)
Clicking on the "connect to a different Google Merchant Center account" button.
#### Emitters
- [`ConnectedGoogleMCAccountCard`](../../js/src/components/google-mc-account-card/connected-google-mc-account-card.js#L45)

### [`gla_mc_account_create_button_click`](../../js/src/components/google-mc-account-card/terms-modal/index.js#L16)
Clicking on the button to create a new Google Merchant Center account, after agreeing to the terms and conditions.
#### Emitters
- [`TermsModal`](../../js/src/components/google-mc-account-card/terms-modal/index.js#L29)

### [`gla_mc_account_reclaim_url_button_click`](../../js/src/components/google-mc-account-card/reclaim-url-card/index.js#L26)
Clicking on the button to reclaim URL for a Google Merchant Center account.
#### Emitters
- [`ReclaimUrlCard`](../../js/src/components/google-mc-account-card/reclaim-url-card/index.js#L41)

### [`gla_mc_account_switch_account_button_click`](../../js/src/components/google-mc-account-card/connect-mc/index.js#L32)
Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | (`switch-url`\|`reclaim-url`) - indicate the button is clicked from which step.
#### Emitters
- [`ReclaimUrlCard`](../../js/src/components/google-mc-account-card/reclaim-url-card/index.js#L41) with `context: 'reclaim-url'`
- [`SwitchUrlCard`](../../js/src/components/google-mc-account-card/switch-url-card/index.js#L40) with `context: 'switch-url'`

### [`gla_mc_account_switch_url_button_click`](../../js/src/components/google-mc-account-card/switch-url-card/index.js#L25)
Clicking on the button to switch URL for a Google Merchant Center account.
#### Emitters
- [`SwitchUrlCard`](../../js/src/components/google-mc-account-card/switch-url-card/index.js#L40)

### [`gla_mc_account_warning_modal_confirm_button_click`](../../js/src/components/google-mc-account-card/warning-modal/index.js#L15)
Clicking on the "Yes, I want a new account" button in the warning modal for creating a new Google Merchant Center account.
#### Emitters
- [`WarningModal`](../../js/src/components/google-mc-account-card/warning-modal/index.js#L29)

### [`gla_mc_phone_number_check`](../../js/src/components/contact-information/usePhoneNumberCheckTrackEventEffect.js#L12)
Check for whether the phone number for Merchant Center exists or not.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`path` | `string` | the path where the check is in.
`exist` | `string` | whether the phone number exists or not.
`isValid` | `string` | whether the phone number is valid or not.
#### Emitters
- [`usePhoneNumberCheckTrackEventEffect`](../../js/src/components/contact-information/usePhoneNumberCheckTrackEventEffect.js#L25)

### [`gla_mc_phone_number_edit_button_click`](../../js/src/components/contact-information/phone-number-card/phone-number-card.js#L103)
Clicking on the Merchant Center phone number edit button.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`view` | `string` | which view the edit button is in. Possible values: `setup-mc`, `settings`.
#### Emitters
- [`PhoneNumberCard`](../../js/src/components/contact-information/phone-number-card/phone-number-card.js#L127)

### [`gla_modal_closed`](../../js/src/utils/tracks.js#L241)
A modal is closed.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which modal is closed
`action` | `string` | Indicates the modal is closed by what action (e.g. `maybe-later`\|`dismiss` \| `create-another-campaign`)    - `maybe-later` is used when the "Maybe later" button on the modal is clicked    - `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, generic "Cancel" button, or pressing ESC    - `create-another-campaign` is used when the button "Create another campaign" is clicked    - `create-paid-campaign` is used when the button "Create paid campaign" is clicked    - `confirm` is used when the button "Confirm", "Save"  or similar generic "Accept" button is clicked
#### Emitters
- [`AttributeMappingTable`](../../js/src/attribute-mapping/attribute-mapping-table.js#L59) When any of the modals is closed
- [`Dashboard`](../../js/src/dashboard/index.js#L34) when CES modal is closed.
- [`ReviewRequest`](../../js/src/product-feed/review-request/index.js#L31) with `action: 'request-review-success' | 'maybe-later' | 'dismiss', context: REQUEST_REVIEW`
- [`SubmissionSuccessGuide`](../../js/src/product-feed/submission-success-guide/index.js#L159) with `action: 'create-paid-campaign' | 'maybe-later' | 'view-product-feed' | 'dismiss'`

### [`gla_modal_content_link_click`](../../js/src/components/guide-page-content/index.js#L28)
Clicking on a text link within the modal content
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which link is clicked
`href` | `string` | Link's URL
#### Emitters
- [`ContentLink`](../../js/src/components/guide-page-content/index.js#L46) with given `context, href`

### [`gla_modal_open`](../../js/src/utils/tracks.js#L254)
A modal is open
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates which modal is open
#### Emitters
- [`AttributeMappingTable`](../../js/src/attribute-mapping/attribute-mapping-table.js#L59) When any of the modals is open with `{ context: 'attribute-mapping-manage-rule-modal' | 'attribute-mapping-create-rule-modal' }`
- [`ReviewRequest`](../../js/src/product-feed/review-request/index.js#L31) with `context: REQUEST_REVIEW`
- [`SubmissionSuccessGuide`](../../js/src/product-feed/submission-success-guide/index.js#L159) with `context: GUIDE_NAMES.SUBMISSION_SUCCESS`

### [`gla_onboarding_complete_button_click`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L47)
Clicking on the skip paid ads button to complete the onboarding flow.
 The 'unknown' value of properties may means:
 - the paid ads setup is not opened
 - the final status has not yet been resolved when recording this event
 - the status is not available, for example, the billing status is unknown if Google Ads account is not yet connected
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`opened_paid_ads_setup` | `string` | Whether the paid ads setup is opened, e.g. 'yes', 'no'
`google_ads_account_status` | `string` | The connection status of merchant's Google Ads addcount, e.g. 'connected', 'disconnected', 'incomplete'
`billing_method_status` | `string` | aaa, The status of billing method of merchant's Google Ads addcount e.g. 'unknown', 'pending', 'approved', 'cancelled'
`campaign_form_validation` | `string` | Whether the entered paid campaign form data are valid, e.g. 'unknown', 'valid', 'invalid'
#### Emitters
- [`exports`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L69)

### [`gla_onboarding_complete_with_paid_ads_button_click`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L39)
Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`budget` | `number` | The budget for the campaign
`audiences` | `string` | The targeted audiences for the campaign
#### Emitters
- [`exports`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L69)

### [`gla_onboarding_open_paid_ads_setup_button_click`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L33)
Clicking on the "Create a paid ad campaign" button to open the paid ads setup in the onboarding flow.
#### Emitters
- [`exports`](../../js/src/setup-mc/setup-stepper/setup-paid-ads/setup-paid-ads.js#L69)

### [`gla_open_ads_account_claim_invitation_button_click`](../../js/src/components/google-ads-account-card/claim-account-button.js#L15)
Clicking on the button to open the invitation page for claiming the newly created Google Ads account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicates the place where the button is located.
`step` | `string` | Indicates the step in the onboarding process.
#### Emitters
- [`ClaimAccountButton`](../../js/src/components/google-ads-account-card/claim-account-button.js#L32) When the user clicks on the button to claim the account.

### [`gla_paid_campaign_step`](../../js/src/utils/tracks.js#L201)
Triggered when moving to another step during creating/editing a campaign.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`triggered_by` | `string` | Indicates which button triggered this event
`action` | `string` | User's action or/and objective (e.g. `go-to-step-2`)
`context` | `string \| undefined` | Indicates where this event happened
#### Emitters
- [`CreatePaidAdsCampaign`](../../js/src/pages/create-paid-ads-campaign/index.js#L46)
	- with `{ context: 'create-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
	- with `{ context: 'create-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.
- [`EditPaidAdsCampaign`](../../js/src/pages/edit-paid-ads-campaign/index.js#L55)
	- with `{ context: 'edit-ads', triggered_by: 'step1-continue-button', action: 'go-to-step2' }`.
	- with `{ context: 'edit-ads', triggered_by: 'stepper-step1-button', action: 'go-to-step1' }`.

### [`gla_request_review`](../../js/src/product-feed/review-request/review-request-modal.js#L19)
Triggered when request review button is clicked
#### Emitters
- [`ReviewRequestModal`](../../js/src/product-feed/review-request/review-request-modal.js#L58)

### [`gla_request_review_failure`](../../js/src/product-feed/review-request/review-request-modal.js#L31)
Triggered when the request review fails
#### Emitters
- [`ReviewRequestModal`](../../js/src/product-feed/review-request/review-request-modal.js#L58)

### [`gla_request_review_issues_solved_checkbox_click`](../../js/src/product-feed/review-request/review-request-modal.js#L37)
Triggered when clicking on the checkbox
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`action` | `'check'\|'uncheck'` | Indicates if the checkbox is checked or unchecked
#### Emitters
- [`ReviewRequestModal`](../../js/src/product-feed/review-request/review-request-modal.js#L58) with `action: 'checked' | 'unchecked'`

### [`gla_request_review_success`](../../js/src/product-feed/review-request/review-request-modal.js#L25)
Triggered when the request review is successful
#### Emitters
- [`ReviewRequestModal`](../../js/src/product-feed/review-request/review-request-modal.js#L58)

### [`gla_reselect_another_final_url_button_click`](../../js/src/components/paid-ads/asset-group/final-url-card.js#L23)
Clicking on the "Or, select another page" button.
#### Emitters
- [`exports`](../../js/src/components/paid-ads/asset-group/final-url-card.js#L39)

### [`gla_setup_ads`](../../js/src/utils/tracks.js#L193)
Triggered on events during ads onboarding
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`triggered_by` | `string` | Indicates which button triggered this event
`action` | `string` | User's action or/and objective (e.g. `leave`, `go-to-step-2`)
#### Emitters
- [`AdsStepper`](../../js/src/setup-ads/ads-stepper/index.js#L28)
	- with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button' , action: 'go-to-step2' | 'go-to-step3' }`.
	- with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
- [`SetupAdsTopBar`](../../js/src/setup-ads/top-bar/index.js#L17) with given `{ triggered_by: 'back-button', action: 'leave' }` when back button is clicked.

### [`gla_setup_ads_faq`](../../js/src/components/paid-ads/faqs-section.js#L76)
Clicking on faq items to collapse or expand it in the Onboarding Flow or creating/editing a campaign
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `string` | FAQ identifier
`action` | `string` | (`expand`\|`collapse`)
#### Emitters
- [`FaqsSection`](../../js/src/components/paid-ads/faqs-section.js#L89)

### [`gla_setup_mc`](../../js/src/utils/tracks.js#L158)
Setup Merchant Center
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`triggered_by` | `string` | Indicates which button triggered this event
`action` | `string` | User's action or/and objective (e.g. `leave`, `go-to-step-2`)
`context` | `string \| undefined` | Indicates which CTA is clicked
#### Emitters
- [`GetStartedCard`](../../js/src/get-started-page/get-started-card/index.js#L23) with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started' }`.
- [`GetStartedWithVideoCard`](../../js/src/get-started-page/get-started-with-video-card/index.js#L23) with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started-with-video' }`.
- [`SavedSetupStepper`](../../js/src/setup-mc/setup-stepper/saved-setup-stepper.js#L39)
	- with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button', 'step3-continue-button', action: 'go-to-step2' | 'go-to-step3' | 'go-to-step4' }`.
	- with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button' | 'stepper-step3-button', action: 'go-to-step1' | 'go-to-step2' | 'go-to-step3' }`.
- [`SetupMCTopBar`](../../js/src/setup-mc/top-bar/index.js#L17) with `{ triggered_by: 'back-button', action: 'leave' }`.

### [`gla_submit_campaign_button_click`](../../js/src/components/paid-ads/asset-group/asset-group.js#L26)
Clicking on the submit button on the campaign creation or editing page.
 If a value is recorded as `unknown`, it's because no assets are imported and therefore unknown.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Indicate the place where the button is located. Possible values: `campaign-creation`, `campaign-editing`.
`action` | `string` | Indicate which submit button is clicked. Possible values: `submit-campaign-and-assets`, `submit-campaign-only`.
`audiences` | `string` | Country codes of the campaign audience countries, e.g. `US,JP,AU`.
`budget` | `string` | Daily average cost of the campaign.
`assets_validation` | `string` | Whether all asset values are valid or at least one invalid. Possible values: `valid`, `invalid`, `unknown`.
`number_of_business_name` | `string` | The number of this asset in string type or `unknown`.
`number_of_marketing_image` | `string` | Same as above.
`number_of_square_marketing_image` | `string` | Same as above.
`number_of_portrait_marketing_image` | `string` | Same as above.
`number_of_logo` | `string` | Same as above.
`number_of_headline` | `string` | Same as above.
`number_of_long_headline` | `string` | Same as above.
`number_of_description` | `string` | Same as above.
`number_of_call_to_action_selection` | `string` | Same as above.
`number_of_final_url` | `string` | Same as above.
`number_of_display_url_path` | `string` | Same as above.
#### Emitters
- [`exports`](../../js/src/components/paid-ads/asset-group/asset-group.js#L60)

### [`gla_table_go_to_page`](../../js/src/utils/tracks.js#L42)
When table pagination is changed by entering page via "Go to page" input.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Name of the table
`page` | `string` | Page number (starting at 1)
#### Emitters
- [`ProductFeedTableCard`](../../js/src/product-feed/product-feed-table-card/index.js#L65) with `context: 'product-feed'`
- [`recordTablePageEvent`](../../js/src/utils/tracks.js#L121) with the given `{ context, page }`.

### [`gla_table_header_toggle`](../../js/src/components/app-table-card/index.js#L12)
Toggling display of table columns
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`report` | `string` | Name of the report table (e.g. `"dashboard" \| "reports-programs" \| "reports-products" \| "product-feed"`)
`column` | `string` | Name of the column
`status` | `'on' \| 'off'` | Indicates if the column was toggled on or off.
#### Emitters
- [`AppTableCard`](../../js/src/components/app-table-card/index.js#L74) upon toggling column visibility
- [`recordColumnToggleEvent`](../../js/src/components/app-table-card/index.js#L29) with given `report: trackEventReportId, column: toggled`

### [`gla_table_page_click`](../../js/src/utils/tracks.js#L50)
When table pagination is clicked
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | Name of the table
`direction` | `string` | Direction of page to be changed. `("next" \| "previous")`
#### Emitters
- [`ProductFeedTableCard`](../../js/src/product-feed/product-feed-table-card/index.js#L65) with `context: 'product-feed'`
- [`recordTablePageEvent`](../../js/src/utils/tracks.js#L121) with the given `{ context, direction }`.

### [`gla_table_sort`](../../js/src/components/app-table-card/index.js#L38)
Sorting table
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`report` | `string` | Name of the report table (e.g. `"dashboard" \| "reports-programs" \| "reports-products" \| "product-feed"`)
`column` | `string` | Name of the column
`direction` | `string` | (`asc`\|`desc`)
#### Emitters
- [`AppTableCard`](../../js/src/components/app-table-card/index.js#L74) upon sorting table by column
- [`recordTableSortEvent`](../../js/src/components/app-table-card/index.js#L55) with given props.

### [`gla_tooltip_viewed`](../../js/src/components/help-popover/index.js#L16)
Viewing tooltip
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`id` | `string` | Tooltip identifier.
#### Emitters
- [`HelpPopover`](../../js/src/components/help-popover/index.js#L32) with the given `id`.

### [`gla_wc_store_address_validation`](../../js/src/components/contact-information/store-address-card.js#L35)
Track how many times and what fields the store address is having validation errors.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`path` | `string` | The path used in the page from which the event tracking was sent, e.g. `"/google/setup-mc"` or `"/google/settings"`.
`subpath` | `string\|undefined` | The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
`country_code` | `string` | The country code of store address, e.g. `"US"`.
`missing_fields` | `string` | The string of the missing required fields of store address separated by comma, e.g. `"city,postcode"`.
#### Emitters
- [`StoreAddressCard`](../../js/src/components/contact-information/store-address-card.js#L56) Whenever the new store address data is fetched after clicking "Refresh to sync" button.

### [`gla_wordpress_account_connect_button_click`](../../js/src/components/wpcom-account-card/connect-wpcom-account-card.js#L17)
Clicking on the button to connect WordPress.com account.
#### Properties
| name | type | description |
| ---- | ---- | ----------- |
`context` | `string` | (`setup-mc`\|`reconnect`) - indicates from which page the button was clicked.
#### Emitters
- [`ConnectWPComAccountCard`](../../js/src/components/wpcom-account-card/connect-wpcom-account-card.js#L27)

<!---
End of `woocommerce-grow-tracking-jsdoc`-generated content.
-->
</woocommerce-grow-tracking-jsdoc>
