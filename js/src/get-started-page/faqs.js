/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FaqsPanel from '.~/components/faqs-panel';
import AppDocumentationLink from '.~/components/app-documentation-link';

const faqItems = [
	{
		trackId: 'what-is-google-merchant-center',
		question: __(
			'What is Google Merchant Center?',
			'google-listings-and-ads'
		),
		answer: __(
			'The Google Merchant Center helps you sync your store and product data with Google and makes the information available for both free listings on the Shopping tab and Google Shopping Ads. That means everything about your stores and products is available to customers when they search on a Google property.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'what-if-i-already-have-free-listings',
		question: __(
			'What if I already have free listings set up for my store?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'If there is an existing claim on your store URL, this WooCommerce integration will reclaim your store URL. This will cause any existing product listings or ads to stop running, and the other verified account will be notified that they have lost their claim.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'Learn more about claiming URLs <link>here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="faqs"
									linkId="claiming-urls"
									href="https://support.google.com/merchants/answer/7527436"
								/>
							),
						}
					) }
				</p>
				<p>
					{ __(
						'If you have an existing Content API feed, it will not be changed, overwritten or deleted by this WooCommerce integration. Instead, products will be added to your existing Content API feed.',
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},
	{
		trackId: 'which-countries-are-available',
		question: __(
			'Which countries are available for Google Listings & Ads?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ createInterpolateElement(
						__(
							'Learn more about supported countries for Google free listings <link>here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="faqs"
									linkId="supported-countries-for-google-free-listings"
									href="https://support.google.com/merchants/answer/10033607"
								/>
							),
						}
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'Learn more about supported countries and currencies for Performance Max campaigns <link>here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="faqs"
									linkId="supported-countries-and-currencies-for-smart-shopping-campaigns"
									href="https://support.google.com/merchants/answer/160637#countrytable"
								/>
							),
						}
					) }
				</p>
			</>
		),
	},
	{
		trackId: 'where-will-my-products-appear',
		question: __(
			'Where will my products appear?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'If you’re selling in the US, then eligible free listings can appear in search results across Google Search, Google Images, and the Google Shopping tab. If you’re selling outside the US, free listings will appear on the Shopping tab.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'If you’re running a Performance Max campaign, your approved products can appear on Google Search, the Shopping tab, Gmail, Youtube and the Google Display Network.',
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},
	{
		trackId: 'will-my-deals-and-promotions-display-on-google',
		question: __(
			'Will my deals and promotions display on Google?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'To show your coupons and promotions on Google Shopping listings, make sure you’re using the latest version of Google Listings & Ads. When you create or update a coupon in your WordPress dashboard under Marketing > Coupons, you’ll see a Channel Visibility settings box on the right: select "Show coupon on Google" to enable. This is currently available in the US only.',
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},
	{
		trackId: 'what-are-smart-shopping-campaigns',
		question: __(
			'What are Performance Max campaigns?',
			'google-listings-and-ads'
		),
		answer: __(
			'Performance Max campaigns are Google Ads that combine Google’s machine learning with automated bidding and ad placements to maximize conversion value and strategically display your ads to people searching for products like yours, at your given budget. The best part? You only pay when people click on your ad.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'how-much-do-smart-shopping-campaigns-cost',
		question: __(
			'How much do Performance Max campaigns cost?',
			'google-listings-and-ads'
		),
		answer: __(
			'Performance Max campaigns are pay-per-click, meaning you only pay when someone clicks on your ads. You can customize your daily budget in Google Listings & Ads but we recommend starting off with the suggested minimum budget, and you can change this budget at any time.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'can-i-run-both-free-listings-and-smart-shopping-campaigns',
		question: __(
			'Can I run both free listings and Performance Max campaigns at the same time?',
			'google-listings-and-ads'
		),
		answer: __(
			'Yes, you can run both at the same time, and we recommend it! In the US, advertisers running free listings and ads together have seen an average of over 50% increase in clicks and over 100% increase in impressions on both free listings and ads on the Shopping tab. Your store is automatically opted into free listings automatically and can choose to run a paid Performance Max campaign.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'how-can-i-get-the-ad-credit-offer',
		question: __(
			'How can I get the $500 ad credit offer?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'Create a new Google Ads account through Google Listings & Ads and a promotional code will be automatically applied to your account. You’ll have 60 days to spend $500 to qualify for the $500 ads credit.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'Ad credit amounts vary by country and region. Full terms and conditions can be found <link>here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="faqs"
									linkId="terms-and-conditions-of-google-ads-coupons"
									href="https://www.google.com/ads/coupons/terms/"
								/>
							),
						}
					) }
				</p>
			</>
		),
	},
];

const Faqs = () => {
	return <FaqsPanel trackName="gla_get_started_faq" faqItems={ faqItems } />;
};

export default Faqs;
