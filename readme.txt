=== Google Listings & Ads ===
Contributors: automattic, google, woocommerce
Tags: woocommerce, google, listings, ads
Requires at least: 5.7
Tested up to: 5.9
Requires PHP: 7.3
Stable tag: 1.12.8
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
- **Boost store traffic and sales** with Performance Max Campaigns.

= Connect your store seamlessly =

Integrate with Google Merchant Center to upload relevant store and product data to Google. Your products will sync automatically to make the information available for free listings, Google Ads, and other Google services.

Create a new Merchant Center account or link an existing one to connect your store and list products across Google for free and  with ads.

= Reach online shoppers with free listings =

Showcase eligible products to shoppers looking for what you offer and drive traffic to your store with Google’s free listings on the Shopping tab.

Your products can also appear on Google Search, Google Images, and Gmail if you’re selling in the United States.

*Learn more about supported countries for Google free listings [here](https://support.google.com/merchants/answer/10033607?hl=en).*

= Boost store traffic and sales with Google Ads =

Grow your business with Performance Max campaigns. Create an ad campaign to promote your products across Google Search, Shopping, YouTube, Gmail, and the Display Network.

Connect your Google Ads account, choose a budget, and launch your campaign straight from your WooCommerce dashboard. You can also review campaign analytics and access automated reports to easily see how your ads are performing.

*Learn more about supported countries and currencies for Performance Max campaigns [here](https://support.google.com/merchants/answer/160637#countrytable).*

= Get $500 in Google Ads credit when you spend your first $500! =

Create a new Google Ads account through Google Listings & Ads and a promotional code will be automatically applied to your account. You’ll have 60 days to spend $500 to qualify for the $500 ads credit. See full terms and conditions [here](https://www.google.com/ads/coupons/terms/).

== Installation ==

= Minimum Requirements =

* WordPress 5.7 or greater
* WooCommerce 5.8 or greater
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

Learn more about supported countries and currencies for Performance Max campaigns [here](https://support.google.com/merchants/answer/160637#countrytable).

= Where will my products appear? =
If you’re selling in the US, then eligible free listings can appear in search results across Google Search, Google Images, and the Google Shopping tab. If you're selling outside the US, free listings will appear on the Shopping tab.

If you’re running a Performance Max campaign, your approved products can appear on Google Search, the Shopping tab, Gmail, Youtube and the Google Display Network.

= Will my deals and promotions display on Google? =
To show your coupons and promotions on Google Shopping listings, make sure you’re using the latest version of Google Listings & Ads.  When you create or update a coupon in your WordPress dashboard under Marketing > Coupons, you’ll see a Channel Visibility settings box on the right: select “Show coupon on Google” to enable. This is currently available in the US only.

= What are Performance Max campaigns? =
Performance Max campaigns are Google Ads that combine Google’s machine learning with automated bidding and ad placements to maximize conversion value and strategically display your ads to people searching for products like yours, at your given budget. The best part? You only pay when people click on your ad.

= How much do Performance Max campaigns cost? =
Performance Max campaigns are pay-per-click, meaning you only pay when someone clicks on your ads. You can customize your daily budget in Google Listings & Ads but we recommend starting off with the suggested minimum budget, and you can change this budget at any time.

= Can I run both free listings and Performance Max campaigns at the same time? =
Yes, you can run both at the same time, and we recommend it! In the US, advertisers running free listings and ads together have seen an average of over 50% increase in clicks and over 100% increase in impressions on both free listings and ads on the Shopping tab. Your store is automatically opted into free listings automatically and can choose to run a paid Performance Max campaign.

== Changelog ==

= 1.12.8 - 2022-05-05 =
* Update - Add the FAQs card for UX improvements on get started page.
* Update - Add the benefits card for UX improvements on get started page.
* Update - Add the customer quotes card for UX improvements on get started page.
* Update - Add the features card for UX improvements on get started page.
* Update - Add the first card with a CTA and a video for UX improvements on get started page.
* Update - Add the get started card for UX improvements on get started page.

= 1.12.7 - 2022-05-04 =
* Fix - Label UI for selecting countries (TreeSelectControl / SupportedCountrySelect).
* Tweak - Refactor, remove `record*Event` utils.
* Tweak - Upgrade @wordpress/scripts to 22.1.0, and the related packages were upgraded to the corresponding versions.
* Tweak - Upgrade the packages of the e2e testing.
* Tweak - Upgrade webpack config to v5, and enhance the config.

= 1.12.6 - 2022-04-29 =
* Fix - Update all products job syncing products

= 1.12.5 - 2022-04-12 =
* Fix - Cache Yoast SEO values per product, to ensure unique values.
* Fix - Feature/tree select control component.
* Fix - Prompt to reconnect when a Jetpack disconnect is detected.
* Tweak - Automatically generate Tracking events docs from JSDoc.
* Tweak - Move Tracking events docs to JSDoc.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/google-listings-and-ads/trunk/changelog.txt).
