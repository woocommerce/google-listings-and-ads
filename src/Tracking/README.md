# Usage Tracking

_Google Listings & Ads_ implements usage tracking, based on the native [WooCommerce Usage Tracking](https://woocommerce.com/usage-tracking/), and is only enabled when WooCommerce Tracking is enabled.

When a store opts in to WooCommerce usage tracking and uses _Google Listings & Ads_, they will also be opted in to the tracking added by _Google Listings & Ads_.

## What is tracked

As in WooCommerce core, only non-sensitive data about how a store is set up and managed is tracked. We **do not track or store personal data** from your clients.

-   Plugin version
-   Settings
    -   WordPress.com account connection status
    -   Google Merchant center account connection status

<!-- TODO: add more tracking information -->

### Tracking events

All event names are prefixed by `wcadmin_gla_`.

- `activated_from_source` - Plugin is activated from the "Add Plugins" page in the admin, and has `utm` query parameters indicating deep linking. Parameters currently tracked (and sent as properties):
    - 	`utm_source`
    - 	`utm_medium`
    - 	`utm_campaign`
    - 	`utm_term`
    - 	`utm_content` 

-   `mc_account_reclaim_url_agreement_check` - Clicking on the checkbox to agree with the implications of reclaiming URL.

    -   `checked`: indicate whether the checkbox is checked or unchecked.

-   `mc_account_warning_modal_confirm_button_click` - Clicking on the "Yes, I want a new account" button in the warning modal for creating a new Google Merchant Center account.

-   `mc_phone_number_check` - Check for whether the phone number for Merchant Center exists or not.

    -   `path`: the path where the check is in.
    -   `exist`: whether the phone number exists or not.
    -   `isValid`: whether the phone number is valid or not.

-   `mc_phone_number_edit_button_click` - Clicking on the Merchant Center phone number edit button.

    -   `view`: which view the edit button is in. Possible values: `setup-mc`, `settings`.

-   `mc_url_switch`

    -   `action` property is `required`: the Merchant Center account has a different, claimed URL and needs to be changed
    -   `action` property is `success`: the Merchant Center account has been changed from blank, updated from a different, unclaimed URL, or after user confirmation of a required change.

-   `modal_closed` - A modal is closed

    -   `context`: indicate which modal is closed
    -   `action`: indicate the modal is closed by what action (e.g. `maybe-later`|`dismiss` | `create-another-campaign`)
        -   `maybe-later` is used when the "Maybe later" button on the modal is clicked
        -   `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, or pressing ESC
        -   `create-another-campaign` is used when the button "Create another campaign" is clicked
        -   `create-paid-campaign` is used when the button "Create paid campaign" is clicked

-   `modal_content_link_click` - Clicking on a text link within the modal content

    -   `context`: indicate which link is clicked
    -   `href`: link's URL

-   `modal_open` - A modal is opend

    -   `context`: indicate which modal is opened

-   `pre_launch_checklist_complete` - Triggered when all checklist items are complete / checked.

-   `setup_ads` - Triggered on events during ads setup and editing

    -   `target`: button ID
    -   `trigger`: action (e.g. `click`)

-   `setup_ads_faq` - Clicking on faq items to collapse or expand it in the Setup Ads page

    -   `id`: (faq identifier)
    -   `action`: (`expand`|`collapse`)

-   `setup_mc` - Setup Merchant Center

    -   `target`: button ID
    -   `trigger`: action (e.g. `click`)

-   `setup_mc_faq` - Clicking on faq items to collapse or expand it in the Setup Merchant Center page

    -   `id`: (faq identifier)
    -   `action`: (`expand`|`collapse`)

-   `gla_site_claim` event

    -   `action` property is `overwrite_required`: the site URL is claimed by another Merchant Center account and overwrite confirmation is required
    -   `action` property is `success`: URL has been successfully set or overwritten.
    -   `action` property is `failure`:
        -   `details` property is `independent_account`: unable to execute site claim because the provided Merchant Center account is not a sub-account of our MCA
        -   `details` property is `google_proxy`: claim failed using the user creds (in the `Merchant` class)
        -   `details` property is `google_manager`: claimed failed using MCA creds (paradoxically in the `Middleware` class)

-   `site_verify_failure` - When a site verification with Google fails

    -   `step` : the step of the process that failed (token, meta-tag, unknown)

-   `site_verify_success` - When a site is successfully verified with Google

-   `table_go_to_page` - When table pagination is changed by entering page via "Go to page" input

    -   `context`: name of the table
    -   `page`: page number (starting at 1)

-   `table_header_toggle` - Toggling display of table columns

    -   `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
    -   `column`: name of the column
    -   `status`: (`on`|`off`)

-   `table_page_click` - When table pagination is clicked

    -   `context`: name of the table
    -   `direction`: direction of page to be changed. `("next" | "previous")`

-   `table_sort` - Sorting table

    -   `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
    -   `column`: name of the column
    -   `direction`: (`asc`|`desc`)

-   `tooltip_viewed` - Viewing tooltip

    -   `id`: (tooltip identifier)

-   `wordpress_account_connect_button_click` - Clicking on the button to connect WordPress.com account.

<!-- -- >
## Developer Info
All new tracking info should be updated in this readme.

New snapshot data for **WC Tracker** should be hooked into `Tracking\Events\TrackerSnapshot::include_snapshot_data()`.

New **Tracks** events should be created in `Tracking\Events\Events` (extending `Tracking\Events\BaseEvent`), and need to be registered in `Tracking\Events\EventTracking::$events`. They should also be registered in the `Internal\DependencyManagement\CoreServiceProvider` class:

```php
$this->conditionally_share_with_tags( Loaded::class );
```

 /Dev Info -->

<woo-tracking-jsdoc>
<!---
Everything below will be automatically generated by `woo-tracking-jsdoc`.
Do not edit it manually!
-->

### [`gla_add_paid_campaign_clicked`](js/src/components/paid-ads/add-paid-campaign-button.js#L15)
"Add paid campaign" button is clicked.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | Indicate the place where the button is located.
`href` | `string` | Indicate the destination where the users is directed to, e.g. `'/google/setup-ads'`.
#### Emitters
- [`AddPaidCampaignButton`](js/src/components/paid-ads/add-paid-campaign-button.js#L46) with given props, when clicked.

### [`gla_ads_account_connect_button_click`](js/src/setup-ads/ads-stepper/setup-accounts/google-ads-account-card/connect-ads/index.js#L24)
Clicking on the button to connect an existing Google Ads account.
#### Emitters
- [`ConnectAds`](js/src/setup-ads/ads-stepper/setup-accounts/google-ads-account-card/connect-ads/index.js#L35) when "Connect" button is clicked.

### [`gla_ads_account_create_button_click`](js/src/setup-ads/ads-stepper/setup-accounts/google-ads-account-card/terms-modal/index.js#L16)
Clicking on the button to create a new Google Ads account, after agreeing to the terms and conditions.
#### Emitters
- [`TermsModal`](js/src/setup-ads/ads-stepper/setup-accounts/google-ads-account-card/terms-modal/index.js#L30) When agreed by clicking "Create account".

### [`gla_ads_set_up_billing_click`](js/src/setup-ads/ads-stepper/setup-billing/setup-card/index.js#L18)
"Set up billing" button for Google Ads account is clicked.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | indicate the place where the button is located, e.g. `setup-ads`.
`link_id` | `string` | a unique ID for the button within the context, e.g. `set-up-billing`.
`href` | `string` | indicate the destination where the users is directed to.
#### Emitters
- [`SetupCard`](js/src/setup-ads/ads-stepper/setup-billing/setup-card/index.js#L34) with `{ context: 'setup-ads', link_id: 'set-up-billing',	href: billingUrl }`

### [`gla_bulk_edit_click`](js/src/product-feed/product-feed-table-card/index.js#L40)
Triggered when the product feed "bulk edit" functionality is being used
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | name of the table
`number_of_items` | `number` | edit how many items
`visibility_to` | `string` | `("sync_and_show" \| "dont_sync_and_show")`
#### Emitters
- [`ProductFeedTableCard`](js/src/product-feed/product-feed-table-card/index.js#L63) with `context: product-feed`

### [`gla_ces_feedback`](js/src/components/customer-effort-score-prompt/index.js#L29)
CES feedback recorded
#### Emitters
- [`CustomerEffortScorePrompt`](js/src/components/customer-effort-score-prompt/index.js#L49) whenever the CES feedback is recorded

### [`gla_ces_modal_open`](js/src/components/customer-effort-score-prompt/index.js#L24)
CES modal open
#### Emitters
- [`CustomerEffortScorePrompt`](js/src/components/customer-effort-score-prompt/index.js#L49) whenever the CES modal is open

### [`gla_ces_snackbar_closed`](js/src/components/customer-effort-score-prompt/index.js#L19)
CES prompt snackbar closed
#### Emitters
- [`CustomerEffortScorePrompt`](js/src/components/customer-effort-score-prompt/index.js#L49) whenever the CES snackbar (notice) is closed

### [`gla_ces_snackbar_open`](js/src/components/customer-effort-score-prompt/index.js#L14)
CES prompt snackbar open
#### Emitters
- [`CustomerEffortScorePrompt`](js/src/components/customer-effort-score-prompt/index.js#L49) whenever the CES snackbar (notice) is open

### [`gla_chart_tab_click`](js/src/reports/summary-section.js#L20)
Triggered when a chart tab is clicked
#### Properties
|   |   |   |
|---|---|---|
`report` | `string` | name of the report (e.g. `"reports-programs" \| "reports-products"`)
`context` | `string` | metric key of the clicked tab (e.g. `"sales" \| "conversions" \| "clicks" \| "impressions" \| "spend"`).
#### Emitters
- [`exports`](js/src/reports/summary-section.js#L39)

### [`gla_contact_information_save_button_click`](js/src/settings/edit-store-address.js#L28)
Triggered when the save button in contact information page is clicked.
#### Emitters
- [`exports`](js/src/settings/edit-store-address.js#L40)

### [`gla_dashboard_edit_program_click`](js/src/dashboard/all-programs-table-card/edit-program-button/edit-program-prompt-modal/index.js#L17)
Triggered when "continue" to edit program button is clicked.
#### Properties
|   |   |   |
|---|---|---|
`programId` | `string` | program id
`url` | `string` | url (free or paid)
#### Emitters
- [`EditProgramPromptModal`](js/src/dashboard/all-programs-table-card/edit-program-button/edit-program-prompt-modal/index.js#L32) when "Continue to edit" is clicked.

### [`gla_datepicker_update`](js/src/utils/recordEvent.js#L45)
Triggered when datepicker (date ranger picker) is updated,
 with report name and data that comes from `DateRangeFilterPicker`'s `onRangeSelect` callback
#### Properties
|   |   |   |
|---|---|---|
`report` | `string` | name of the report (e.g. `"dashboard" \| "reports-programs" \| "reports-products" \| "product-feed"`)
`compare` | `string` | Value selected in datepicker.
`period` | `string` | Value selected in datepicker.
`before` | `string` | Value selected in datepicker.
`after` | `string` | Value selected in datepicker.
#### Emitters
- [`AppDateRangeFilterPicker`](js/src/dashboard/app-date-range-filter-picker/index.js#L27)
- [`ProductsReportFilters`](js/src/reports/products/products-report-filters.js#L44)
- [`ProgramsReportFilters`](js/src/reports/programs/programs-report-filters.js#L46)
- [`recordDatepickerUpdateEvent`](js/src/utils/recordEvent.js#L70)

### [`gla_disconnected_accounts`](js/src/settings/disconnect-accounts/index.js#L28)
Accounts are disconnected from the Setting page
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | (`all-accounts`\|`ads-account`) - indicate which accounts have been disconnected.

### [`gla_documentation_link_click`](js/src/components/app-documentation-link/index.js#L6)
When a documentation link is clicked.
#### Properties
|   |   |   |
|---|---|---|
`link_id` | `string` | link identifier
`context` | `string` | indicate which link is clicked
`href` | `string` | link's URL
#### Emitters
- [`AppDocumentationLink`](js/src/components/app-documentation-link/index.js#L29)

### [`gla_edit_mc_phone_number`](js/src/components/contact-information/phone-number-card/phone-number-card-preview.js#L13)
Triggered when phone number edit button is clicked.
#### Properties
|   |   |   |
|---|---|---|
`path` | `string` | The path used in the page, e.g. `"/google/settings"`.
`subpath` | `string` | The subpath used in the page, or `undefined` when there is no subpath.
#### Emitters
- [`PhoneNumberCardPreview`](js/src/components/contact-information/phone-number-card/phone-number-card-preview.js#L32) Whenever "Edit" is clicked.

### [`gla_edit_mc_store_address`](js/src/components/contact-information/store-address-card.js#L125)
Trigger when store address edit button is clicked.
 Before `1.5.0` this name was used for tracking clicking "Edit in settings" to edit the WC address. As of `>1.5.0`, that event is now tracked as `edit_wc_store_address`.
#### Properties
|   |   |   |
|---|---|---|
`path` | `string` | The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
`subpath` | `string\|undefined` | The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
#### Emitters
- [`StoreAddressCardPreview`](js/src/components/contact-information/store-address-card.js#L146) Whenever "Edit" is clicked.

### [`gla_edit_product_click`](js/src/product-feed/product-feed-table-card/index.js#L49)
Triggered when edit links are clicked from product feed table.
#### Properties
|   |   |   |
|---|---|---|
`status` | `string` | `("approved" \| "partially_approved" \| "expiring" \| "pending" \| "disapproved" \| "not_synced")`
`visibility` | `string` | `("sync_and_show" \| "dont_sync_and_show")`
#### Emitters
- [`ProductFeedTableCard`](js/src/product-feed/product-feed-table-card/index.js#L63)

### [`gla_edit_product_issue_click`](js/src/product-feed/issues-table-card/index.js#L83)
Triggered when edit links are clicked from Issues to resolve table.
#### Properties
|   |   |   |
|---|---|---|
`code` | `string` | issue code returned from Google
`issue` | `string` | issue description returned from Google
#### Emitters
- [`IssuesTableCard`](js/src/product-feed/issues-table-card/index.js#L94)

### [`gla_edit_wc_store_address`](js/src/components/contact-information/store-address-card.js#L23)
Triggered when store address "Edit in WooCommerce Settings" button is clicked.
 Before `1.5.0` it was called `edit_mc_store_address`.
#### Properties
|   |   |   |
|---|---|---|
`path` | `string` | The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
`subpath` | `string\|undefined` | The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
#### Emitters
- [`exports`](js/src/components/contact-information/store-address-card.js#L40) Whenever "Edit in WooCommerce Settings" button is clicked.

### [`gla_filter`](js/src/utils/recordEvent.js#L74)
Triggered when changing products & variations filter.
#### Properties
|   |   |   |
|---|---|---|
`report` | `string` | name of the report (e.g. `"reports-products"`)
`filter` | `string` | value of the filter (e.g. `"all" \| "single-product" \| "compare-products"`)
`variationFilter` | `string \| undefined` | value of the variation filter (e.g. `undefined \| "single-variation" \| "compare-variations"`)
#### Emitters
- [`ProductsReportFilters`](js/src/reports/products/products-report-filters.js#L44)
- [`ProgramsReportFilters`](js/src/reports/programs/programs-report-filters.js#L46)
- [`recordFilterEvent`](js/src/utils/recordEvent.js#L94)

### [`gla_free_ad_credit_country_click`](js/src/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.js#L16)
Clicking on the link to view free ad credit value by country.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | indicate which page the link is in.
#### Emitters
- [`FreeAdCredit`](js/src/setup-ads/ads-stepper/setup-accounts/free-ad-credit/index.js#L26)

### [`gla_free_campaign_edited`](js/src/edit-free-campaign/index.js#L79)
Saving changes to the free campaign.
#### Emitters
- [`exports`](js/src/edit-free-campaign/index.js#L95)

### [`gla_get_started_faq`](js/src/get-started-page/faqs.js#L219)
Clicking on getting started page faq item to collapse or expand it.
#### Properties
|   |   |   |
|---|---|---|
`id` | `string` | (faq identifier)
`action` | `string` | (`expand`\|`collapse`)
#### Emitters
- [`Faqs`](js/src/get-started-page/faqs.js#L230)

### [`gla_get_started_notice_link_click`](js/src/get-started-page/unsupported-notices/index.js#L26)
Clicking on a text link within the notice on the Get Started page.
#### Properties
|   |   |   |
|---|---|---|
`link_id` | `string` | link identifier
`context` | `string` | indicate which link is clicked
`href` | `string` | link's URL
#### Emitters
- [`UnsupportedLanguage`](js/src/get-started-page/unsupported-notices/index.js#L38) with `{	context: "get-started", link_id: "supported-languages" }`
- [`UnsupportedCountry`](js/src/get-started-page/unsupported-notices/index.js#L84) with `{	context: "get-started", link_id: "supported-countries" }`

### [`gla_google_account_connect_button_click`](js/src/utils/recordEvent.js#L152)
Clicking on the button to connect Google account.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | (`setup-mc`\|`setup-ads`\|`reconnect`) - indicate the button is clicked from which page.
`action` | `string` | (`authorization`\|`scope`) 	- `authorization` is used when the plugin has not been authorized yet and requests Google account access and permission scopes from users.   - `scope` is used when requesting required permission scopes from users in order to proceed with more plugin functions. Added with the Partial OAuth feature (aka Incremental Authorization).
#### Emitters
- [`exports`](js/src/components/google-account-card/connect-google-account-card.js#L21) with `{ action: 'authorization', context: 'reconnect' | 'setup-mc' }`
- [`exports`](js/src/components/google-account-card/request-full-access-google-account-card.js#L24) with `{ action: 'scope', context: 'reconnect' | 'setup-mc' }`
- [`exports`](js/src/setup-ads/ads-stepper/setup-accounts/google-ads-account-card/authorize-ads.js#L21) with `{ action: 'scope', context: 'setup-ads' }`

### [`gla_google_account_connect_different_account_button_click`](js/src/components/google-account-card/connected-google-account-card.js#L15)
Clicking on the "connect to a different Google account" button.
#### Emitters
- [`exports`](js/src/components/google-account-card/connected-google-account-card.js#L32)

### [`gla_google_ads_account_link_click`](js/src/setup-ads/ads-stepper/setup-billing/billing-saved-card/index.js#L19)
Clicking on a Google Ads account text link.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | indicate which page / module the link is in
`href` | `string` | where the user is redirected
`link_id` | `string` | a unique ID for the link within the page / module
#### Emitters
- [`BillingSavedCard`](js/src/setup-ads/ads-stepper/setup-billing/billing-saved-card/index.js#L31) with `{ context: 'setup-ads', link_id: 'google-ads-account' }`

### [`gla_google_mc_link_click`](js/src/utils/recordEvent.js#L162)
Clicking on a Google Merchant Center link.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | indicate which page / module the link is in
`href` | `string` | link's URL
#### Emitters
- [`FreePerformanceCard`](js/src/dashboard/summary-section/index.js#L22) with `{ context: 'dashboard' }`
- [`MetricNumber`](js/src/reports/metric-number.js#L42) with `{ context: 'reports' }`

### [`gla_help_click`](js/src/components/help-icon-button.js#L12)
"Help" button is clicked.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | indicate the place where the button is located, e.g. `setup-ads`.
#### Emitters
- [`HelpIconButton`](js/src/components/help-icon-button.js#L30)

### [`gla_launch_paid_campaign_button_click`](js/src/utils/recordEvent.js#L127)
Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign
#### Properties
|   |   |   |
|---|---|---|
`audiences` | `string` | country codes of the paid campaign audience countries, e.g. `'US,JP,AU'`. This means the campaign is created with the multi-country targeting feature. Before this feature support, it was implemented as 'audience'.
`budget` | `string` | daily average cost of the paid campaign
#### Emitters
- [`CreatePaidAdsCampaignForm`](js/src/pages/create-paid-ads-campaign/create-paid-ads-campaign-form.js#L28) on submit
- [`SetupAdsForm`](js/src/setup-ads/setup-ads-form.js#L24) on submit
- [`recordLaunchPaidCampaignClickEvent`](js/src/utils/recordEvent.js#L143)

### [`gla_mc_account_connect_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/connect-mc/index.js#L25)
Clicking on the button to connect an existing Google Merchant Center account.
#### Emitters
- [`ConnectMC`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/connect-mc/index.js#L41)

### [`gla_mc_account_connect_different_account_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/connected-card.js#L21)
Clicking on the "connect to a different Google Merchant Center account" button.
#### Emitters
- [`ConnectedCard`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/connected-card.js#L32)

### [`gla_mc_account_create_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/terms-modal/index.js#L16)
Clicking on the button to create a new Google Merchant Center account, after agreeing to the terms and conditions.
#### Emitters
- [`TermsModal`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/terms-modal/index.js#L28)

### [`gla_mc_account_reclaim_url_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/reclaim-url-card/index.js#L26)
Clicking on the button to reclaim URL for a Google Merchant Center account.
#### Emitters
- [`ReclaimUrlCard`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/reclaim-url-card/index.js#L40)

### [`gla_mc_account_switch_account_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/connect-mc/index.js#L31)
Clicking on the "Switch account" button to select a different Google Merchant Center account to connect.
#### Properties
|   |   |   |
|---|---|---|
`context` | `string` | (`switch-url`\|`reclaim-url`) - indicate the button is clicked from which step.
#### Emitters
- [`ReclaimUrlCard`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/reclaim-url-card/index.js#L40) with `context: 'reclaim-url'`
- [`SwitchUrlCard`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/switch-url-card/index.js#L40) with `context: 'switch-url'`

### [`gla_mc_account_switch_url_button_click`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/switch-url-card/index.js#L25)
Clicking on the button to switch URL for a Google Merchant Center account.
#### Emitters
- [`SwitchUrlCard`](js/src/setup-mc/setup-stepper/setup-accounts/google-mc-account/switch-url-card/index.js#L40)

<!---
End of `woo-tracking-jsdoc`-generated content.
-->
</woo-tracking-jsdoc>
