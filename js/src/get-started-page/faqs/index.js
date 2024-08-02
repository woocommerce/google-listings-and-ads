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
import './index.scss';

const faqItems = [
	{
		trackId: 'what-do-i-need-to-get-started',
		question: __(
			'What do I need to get started?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'In order to sync your WooCommerce store with Google and begin showcasing your products online, you will need to provide the following during setup; Google account access, target audience, shipping information, tax rate information (required for US only), and ensure your store is running on a compatible PHP version. <link>Learn more.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="general-requirements"
								href="https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#general-requirements"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-if-i-already-have-free-listings',
		question: __(
			'What if I already have Google listings or ads set up? Will syncing my store replace my current Google listings?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'Once you link an existing account to connect your store, your Shopping ads and free listings will stop running. You’ll need to re-upload your feed and product data in order to run Shopping ads and show free listings.',
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
		trackId: 'is-my-store-ready-to-sync-with-google',
		question: __(
			'Is my store ready to sync with Google?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'In order to meet the Google Merchant Center requirements make sure your website has the following; secure checkout process and payment information, refund and return policies, billing terms and conditions, business contact information. <link>Learn more.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="google-merchant-center-requirements"
								href="https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#google-merchant-center-requirements"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-is-a-performance-max-campaign',
		question: __(
			'What is a Performance Max campaign?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'Performance Max campaigns make it easy to connect your WooCommerce store to Google Shopping ads so you can showcase your products to shoppers across Google Search, Maps, Shopping, YouTube, Gmail, the Display Network and Discover feed to drive traffic and sales for your store. <link>Learn more.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="performance-max"
								href="https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-are-free-listings',
		question: __( 'What are free listings?', 'google-listings-and-ads' ),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'Google Free Listings allows stores to showcase eligible products to shoppers looking for what you offer and drive traffic to your store with Google’s free listings on the Shopping tab. Your products can also appear on Google Search, Google Images, and Gmail if you’re selling in the United States. <link>Learn more.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="free-listings"
								href="https://woocommerce.com/document/google-listings-and-ads/#free-listings-on-google"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId:
			'where-to-track-free-listings-and-performance-max-campaign-performance',
		question: __(
			'Where can I track my free listings and Performance Max campaign performance?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'Once your free listings and Performance Max campaigns are set up, you will be able to track your performance straight from your WooCommerce dashboard. You can view your reports yearly, quarterly, monthly, weekly, or daily. The following metrics will be visible within your report: conversions, clicks, impressions, total sales and total spend. <link>Learn more.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="campaign-analytics"
								href="https://woocommerce.com/document/google-listings-and-ads/#getting-started-with-campaign-analytics"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'how-to-sync-products-to-google-free-listings',
		question: __(
			'How do I sync my products to Google free listings?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'The Google for WooCommerce plugin allows you to upload your store and product data to Google. Your products will sync automatically to make relevant information available for free listings, Google Ads, and other Google services. You can create a new Merchant Center account or link an existing account to connect your store and list products across Google.',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId: 'can-i-run-both-shopping-ads-and-free-listings-campaigns',
		question: __(
			'Can I run both Shopping ads and free listings campaigns at the same time?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'Yes, you can run both at the same time, and we recommend it! In the US, advertisers running free listings and ads together have seen an average of over 50% increase in clicks and over 100% increase in impressions on both free listings and ads on the Shopping tab. Your store is automatically opted into free listings automatically and can choose to run a paid Performance Max campaign.',
					'google-listings-and-ads'
				) }
			</p>
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
						'Create a new Google Ads account through Google for WooCommerce and a promotional code will be automatically applied to your account. You’ll have 60 days to spend $500 to qualify for the $500 ads credit.',
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

/**
 * @fires gla_faq with `{ context: 'get-started', id: 'what-do-i-need-to-get-started', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-do-i-need-to-get-started', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-if-i-already-have-free-listings', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-if-i-already-have-free-listings', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'is-my-store-ready-to-sync-with-google', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'is-my-store-ready-to-sync-with-google', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-is-a-performance-max-campaign', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-is-a-performance-max-campaign', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-are-free-listings', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'what-are-free-listings', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'where-to-track-free-listings-and-performance-max-campaign-performance', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'where-to-track-free-listings-and-performance-max-campaign-performance', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'how-to-sync-products-to-google-free-listings', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'how-to-sync-products-to-google-free-listings', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'can-i-run-both-shopping-ads-and-free-listings-campaigns', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'can-i-run-both-shopping-ads-and-free-listings-campaigns', action: 'collapse' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'how-can-i-get-the-ad-credit-offer', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'get-started', id: 'how-can-i-get-the-ad-credit-offer', action: 'collapse' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'general-requirements', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#general-requirements' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'claiming-urls', href: 'https://support.google.com/merchants/answer/7527436' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'google-merchant-center-requirements', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/requirements/#google-merchant-center-requirements' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'performance-max', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'free-listings', href: 'https://woocommerce.com/document/google-listings-and-ads/#free-listings-on-google' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'campaign-analytics', href: 'https://woocommerce.com/document/google-listings-and-ads/#getting-started-with-campaign-analytics' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'terms-and-conditions-of-google-ads-coupons', href: 'https://www.google.com/ads/coupons/terms/' }`.
 */
const Faqs = () => {
	return (
		<FaqsPanel
			className="gla-get-started-faqs"
			trackName="gla_faq"
			context="get-started"
			faqItems={ faqItems }
		/>
	);
};

export default Faqs;
