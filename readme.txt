=== Google Listings & Ads ===
Contributors: automattic, google, woocommerce
Tags: woocommerce, google, listings, ads
Requires at least: 5.5
Tested up to: 5.7
Requires PHP: 7.3
Stable tag: 1.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Native integration with Google that allows merchants to easily display their products across Google’s network.

== Description ==

https://www.youtube.com/watch?v=lYCx7ZqA1uo

Google Listings & Ads makes it simple to showcase your products to shoppers across Google. Whether you’re brand new to digital advertising or a marketing expert, you can expand your reach and grow your business, for free and with ads.

Sync your store with Google to list products for free, run paid ads, and track performance straight from your store dashboard.

With Google Listings & Ads:
- **Connect your store seamlessly** with Google Merchant Center.
- **Reach online shoppers** with free listings.
- **Boost store traffic and sales** with Smart Shopping Campaigns.

= Connect your store seamlessly =

Integrate with Google Merchant Center to upload relevant store and product data to Google. Your products will sync automatically to make the information available for free listings, Google Ads, and other Google services.

Create a new Merchant Center account or link an existing one to connect your store and list products across Google for free and  with ads.

= Reach online shoppers with free listings =

Showcase eligible products to shoppers looking for what you offer and drive traffic to your store with Google’s free listings on the Shopping tab.

Your products can also appear on Google Search, Google Images, and Gmail if you’re selling in the United States.

*Learn more about supported countries for Google free listings [here](https://support.google.com/merchants/answer/10033607?hl=en).*

= Boost store traffic and sales with Google Ads =

Grow your business with Smart Shopping campaigns. Create an ad campaign to promote your products across Google Search, Shopping, YouTube, Gmail, and the Display Network.

Connect your Google Ads account, choose a budget, and launch your campaign straight from your WooCommerce dashboard. You can also review campaign analytics and access automated reports to easily see how your ads are performing.

*Learn more about supported countries and currencies for Smart Shopping campaigns [here](https://support.google.com/merchants/answer/160637#countrytable).*

= Get started with up to $150 in ad credit when you create a Google Ads account =

Get up to  $150\* in ad credit to help you get started on Smart Shopping Campaigns. The promotional code will be applied when you start spending and serve your first ad impression, and whatever you spend over the next 30 days, up to $150, will be added back to your account.

*\*Ad credit amounts vary by country and region.*

= The eligibility criteria: =
- The account has no other promotions applied.
- The account is billed to a country where Google Partners promotions are offered.
- The account served its first ad impression within the last 14 days.

*Review the static terms [here](http://www.google.com/ads/coupons/terms.html).*

== Installation ==

= Minimum Requirements =

* WordPress 5.5 or greater
* WooCommerce 5.2 or greater
* PHP version 7.3 or greater (PHP 7.4 or greater is recommended)
* MySQL version 5.6 or greater

Visit the [WooCommerce server requirements documentation](https://docs.woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Google Listings and Ads” and click Search Plugins. Once you’ve found this plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Where can I report bugs or contribute to the project? =

Bugs should be reported in the [Google Listings and Ads GitHub repository](https://github.com/woocommerce/google-listings-and-ads/).

= This is awesome! Can I contribute? =

Yes you can! Join in on our [GitHub repository](https://github.com/woocommerce/google-listings-and-ads/) :)

Release and roadmap notes available on the [WooCommerce Developers Blog](https://woocommerce.wordpress.com/)

== FAQ ==

= What is Google Merchant Center? =
The Google Merchant Center helps you sync your store and product data with Google and makes the information available for both free listings on the Shopping tab and Google Shopping Ads. That means everything about your stores and products is available to shoppers when they search on a Google property.

= Which countries are available for Google Listings & Ads? =
Learn more about supported countries for Google free listings [here](https://support.google.com/merchants/answer/10033607?hl=en).

Learn more about supported countries and currencies for Smart Shopping campaigns [here](https://support.google.com/merchants/answer/160637#countrytable).

= Where will my products appear? =
If you’re selling in the US, then eligible free listings can appear in search results across Google Search, Google Images, and the Google Shopping tab. If you're selling outside the US, free listings will appear on the Shopping tab.

If you’re running a Smart Shopping campaign, your approved products can appear on Google Search, the Shopping tab, Gmail, Youtube and the Google Display Network.

= What are Smart Shopping campaigns? =
Smart Shopping campaigns are Google Ads that combine Google’s machine learning with automated bidding and ad placements to maximize conversion value and strategically display your ads to people searching for products like yours, at your given budget. The best part? You only pay when people click on your ad.

= How much do Smart Shopping campaigns cost? =
Smart Shopping campaigns are pay-per-click, meaning you only pay when someone clicks on your ads. You can customize your daily budget in Google Listings & Ads but we recommend starting off with the suggested minimum budget, and you can change this budget at any time.

= Can I run both free listings and Smart Shopping campaigns at the same time? =
Yes, you can run both at the same time, and we recommend it! In the US, advertisers running free listings and ads together have seen an average of over 50% increase in clicks and over 100% increase in impressions on both free listings and ads on the Shopping tab. Your store is automatically opted into free listings automatically and can choose to run a paid Smart Shopping campaign.

== Changelog ==

= 1.1.0 - 2021-06-23 =
* Fix - Adds warning message and checkbox for reclaiming URL in Google Merchant Center account connection setup.
* Fix - Align not synced product stats.
* Fix - Automatically set default values for Setup MC and Edit Free Listings' Target Audience.
* Fix - Cancel the focus from the "Remove" links after removing a program from the programs table.
* Fix - Catch invalid product ID.
* Fix - Cleanup target countries per product.
* Fix - Clear product stats cache on every product sync .
* Fix - Combine the "flat" and "manual" options of shipping rate and time in the MC setup flow to avoid possible errors caused by inconsistent shipping setup logic.
* Fix - Correct formatting string of "day" for status box on the Product Feed page.
* Fix - Handle null postmeta values gracefully.
* Fix - Include the Ads account currency when checking the status.
* Fix - Prevent conflicts when storing the channel visibility value.
* Fix - Resolve Guzzle Conflict with other plugins.
* Fix - Strip shortcodes used in the product description when syncing to Google.
* Fix - The incorrect enabled state after removing a disabled program from the programs table.
* Fix - Update deprecated `stylelint-config-wordpress` to `@wordpress/stylelint-config@19.0.4`,.
* Fix - Use WooCommerce CRUD class methods to handle metadata.
* Fix - Use batch productstatuses.
* Fix - Use get_home_url for retrieving the site URL.
* Fix - Use product's short description if no description is set.
* Tweak - Optimize product queries.
* Tweak - Reduce the default productstatus batch size.
* Tweak - Set batch size to 100 for the "update all products" job.
* Tweak - Show "Unavailable" and more descriptive tooltip when Google reports API fails.
* Tweak - Standardize action and filter hook prefix.
* Tweak - Use target countries as shipping destinations.
* Tweak - WC 5.4 compatibility.
* Update - Increase required minimum WordPress and WooCommerce versions.

= 1.0.0 - 2021-06-08 =
* Fix - Add Tracks events for site claim and URL switching.
* Fix - Add debugging logs for product syncer.
* Fix - Add event tracking when clicking on the chart tabs in the report pages.
* Fix - Add event trackings when the "Launch paid campaign" buttons are clicked.
* Fix - Add status box in the Product Feed page.
* Fix - Add table's pagination tracking events to the product feed page.
* Fix - Add track events for account connections.
* Fix - Add validations to fix that the free listings setup/edit forms could be submitted with a negative shipping rate/time.
* Fix - Bump TravisCI's OS and node version to match the one used for the release.
* Fix - Change "disconnect all accounts" modal text.
* Fix - Change error message and add Open Google MC button to the Dashboard.
* Fix - Change to use batch upsert actions for saving shipping data on the Edit Free Listings page.
* Fix - Check product exists with helper function.
* Fix - Cleanup synced product IDs on settings change.
* Fix - Do not request ads reports when the setup is incomplete.
* Fix - Double check product's sync ready status returned by repository.
* Fix - Expose pre-sync errors.
* Fix - Fall back to 'SurfacesAcrossGoogle' status if 'Shopping' isn't available for Product Feed.
* Fix - Fix compatibility issue that lacks required class of new WC Navigation in supported WC versions.
* Fix - Fix fatal error when duplicating and trashing synced variable products.
* Fix - Fix the alignment of label and helper next to radio and checkbox.
* Fix - Fix the problem of the "Create another campaign" button not working.
* Fix - Hide the ChannelVisibilityMetaBox for unsupported products.
* Fix - Hide unpublished products from the product feed.
* Fix - Include pre-sync product errors in the issues API.
* Fix - Make the free shipping threshold be able to set up with $0.
* Fix - Modify `path` in URL to make additional pages work with WooCommerce Navigation.
* Fix - Only submit 'Published' products.
* Fix - Optimize presync error to issue collation process.
* Fix - Override values for enhanced free listings issue.
* Fix - Prevent render breaking when getting errors from report API in the programs report page.
* Fix - Product titles for Free Listing reports.
* Fix - Refactor product meta to use product object instead of ID.
* Fix - Remove Checkbox.
* Fix - Reports mocked responses.
* Fix - Resolve getLabels immediately, if free listings are requested. ….
* Fix - Retrieve product IDs and use update_post_meta.
* Fix - Return empty if no matching attributes found.
* Fix - Scheduled sync count.
* Fix - Shipping rates and shipping times: Add and edit modals - validation logic.
* Fix - Show selected program label in the filter on program report page load.
* Fix - Small ProductQueryFeedHelper Fix.
* Fix - Sort list of supported countries.
* Fix - Throw an error if no ID is provided.
* Fix - Tracking doc tweaks.
* Fix - Tracking settings.
* Fix - Use empty check for campaign name.
* Fix - Use product name or title in products report.
* Fix - Validate required and incompatible plugins.
* Fix - Workaround `woocommerce/data` dependency issues, reset `package-lock.json`.

= 0.6.0 - 2021-05-27 =
* Fix - Add FAQs to step 1 of the MC setup flow.
* Fix - Add extra product attributes.
* Fix - Add validations for the main steps of edit free listings.
* Fix - Admin Notes 2 to 4.
* Fix - Aggregate intervals from free and paid campaigns, render programs report w/o waiting for secondary request.
* Fix - Change JetPack connection name.
* Fix - Changes to Success Modal after first setup.
* Fix - Connect programs report page to the API data.
* Fix - Connect programs report table to API data.
* Fix - Consistent currency format across all summary list usages.
* Fix - Edit the channel visibility of products on the Product Feed page.
* Fix - Error notice if WooCommerce Admin isn't active.
* Fix - Fix fatal in Product Feed API.
* Fix - Get started copy updates.
* Fix - Implement the deletion feature of paid campaigns for the dashboard page.
* Fix - Integration with new WC Navigation.
* Fix - Make `getReport` ignore unsupported orderby query params.
* Fix - Make unit-tests run with @woocommerce packages.
* Fix - Make unit-tests run with `woocommerce/date` (~`/components`~) dependency.
* Fix - Move @woocommerce/* dependency tests to /tests/unit.
* Fix - Note lack of support for IE in `README.md`.
* Fix - Prefetch product feed data to prevent multiple duplicated Google API requests.
* Fix - REST endpoint for batch product channel visibility updates.
* Fix - Redirect to onboarding / get started page on plugin activation.
* Fix - Remove product feed coming soon notice and show reports by default.
* Fix - Run unit tests on TravisCI,.
* Fix - Silently skip Product Feed products that are no longer in WooCommerce .
* Fix - Sort report API results by date index.
* Fix - Sort the merged programs table.
* Tweak - WC 5.4 compatibility.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/google-listings-and-ads/trunk/changelog.txt).
