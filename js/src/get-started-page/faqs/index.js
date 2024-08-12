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

const linkMc = (
	<AppDocumentationLink
		context="faqs"
		linkId="google-merchant-center-link"
		href="https://woocommerce.com/document/google-for-woocommerce/#connect-your-store-with-google-merchant-center"
	/>
);

const linkPmax = (
	<AppDocumentationLink
		context="faqs"
		linkId="performance-max-link"
		href="https://woocommerce.com/document/google-for-woocommerce/get-started/google-performance-max-campaigns/"
	/>
);
const faqItems = [
	{
		trackId: 'what-is-google-merchant-center',
		question: __(
			'What is Google Merchant Center?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						"<link>Google Merchant Center</link> is like a digital storefront for your products on Google. It's where you upload and manage information about your products, like titles, descriptions, images, prices, and availability. This data is used to create product listings that can appear across Google.",
						'google-listings-and-ads'
					),
					{
						link: linkMc,
					}
				) }
			</p>
		),
	},
	{
		trackId: 'why-should-i-connect-google-merchant-center',
		question: __(
			'Why should I connect to Google Merchant Center?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ createInterpolateElement(
						__(
							'By syncing your product information to <linkMc>Google Merchant Center</linkMc>, your products can appear in relevant Google searches, Shopping tab, image searches, and even on other platforms like YouTube. When running <linkPmax>Performance Max campaigns</linkPmax>, Google Merchant Center ensures that shoppers see the most up-to-date and accurate information about your product feed, reducing confusion and improving the chances of a purchase.',
							'google-listings-and-ads'
						),
						{
							linkMc,
							linkPmax,
						}
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
			<p>
				{ createInterpolateElement(
					__(
						'To show your coupons and promotions on Google Shopping listings, make sure you’re using the latest version of Google for WooCommerce. When you create or update a coupon in your WordPress dashboard under Marketing > Coupons, you’ll see a Channel Visibility settings box on the right: select “Show coupon on Google” to enable it. <link>Learn more</link> about managing promotions for Google for WooCommerce. This feature is currently available in Australia, Canada, Germany, France, India, the United Kingdom, and the United States.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="google-promotions-using-woocommerce"
								href="https://support.google.com/merchants/answer/11338950#zippy=%2Cmanage-promotions-using-woocommerce"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-is-product-sync',
		question: __( 'What is Product Sync?', 'google-listings-and-ads' ),
		answer: (
			<>
				<p>
					{ __(
						'Product Sync is a feature fully integrated into WooCommerce’s management platform that automatically lets you sync your product feed to Google Merchant Center. It will sync all your WooCommerce product data, and you can also add or edit products individually or in bulk. To ensure products are approved by Google, check that your product feed includes the following information:',
						'google-listings-and-ads'
					) }
				</p>
				<ul>
					<li>
						{ __(
							'General product information',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'Unique product identifiers',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'Data requirements for specific categories (auto-assigned by Google):',
							'google-listings-and-ads'
						) }
						<ul>
							<li>
								{ __(
									'Apparel & Accessories',
									'google-listings-and-ads'
								) }
							</li>
							<li>
								{ __( 'Media', 'google-listings-and-ads' ) }
							</li>
							<li>
								{ __( 'Books', 'google-listings-and-ads' ) }
							</li>
						</ul>
					</li>
				</ul>
			</>
		),
	},
	{
		trackId:
			'where-do-i-manage-my-product-feed-and-my-google-ads-campaigns',
		question: __(
			'Where do I manage my product feed and my Google Ads campaigns?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'You can manage and edit all of your products and your Google Ads campaigns right from your WooCommerce dashboard and on the WooCommerce Mobile App.',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId: 'where-will-my-products-appear',
		question: __(
			'Where will my products appear?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'Once you start running a <linkPmax>Performance Max ads campaign</linkPmax>, your approved products will reach more shoppers to help grow your business by being shown on Google Search, Google Maps, the Shopping tab, Gmail, Youtube, the Google Display Network, and Discover feed.',
						'google-listings-and-ads'
					),
					{
						linkPmax,
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-are-performance-max-campaigns',
		question: __(
			'What are Performance Max campaigns?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'<linkPmax>Performance Max campaigns</linkPmax> help you combine your expertise with Google AI to reach your most valuable customers and drive sales. Just set your goals and budget and Google AI will get your ads seen by the right customers at the right time across Google Search, Google Maps, the Shopping tab, Gmail, Youtube, the Google Display Network, and Discover feed.',
						'google-listings-and-ads'
					),
					{
						linkPmax,
					}
				) }
			</p>
		),
	},
	{
		trackId: 'how-much-do-performance-max-campaigns-cost',
		question: __(
			'How much do Performance Max campaigns cost?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'<linkPmax>Performance Max campaigns</linkPmax> are pay-per-click, meaning you only pay when someone clicks on your ads. To get the best results and ensure your products reach the right customers, we recommend starting with the suggested Google for WooCommerce minimum daily budget for your <linkPmax>Performance Max campaign</linkPmax>. This helps jumpstart your campaign and drive early conversions. You can always adjust your budget later as you see what works best for your business.',
						'google-listings-and-ads'
					),
					{
						linkPmax,
					}
				) }
			</p>
		),
	},
	{
		trackId:
			'can-i-sync-my-products-and-run-performance-max-campaigns-on-google-for-woocommerce-at-the-same-time',
		question: __(
			'Can I sync my products and run Performance Max campaigns on Google for WooCommerce at the same time?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'Yes, you can run both at the same time, and we recommend you do! Once you sync your store it’s automatically listed on Google, so you can choose to run a paid <linkPmax>Performance Max campaign</linkPmax> as soon as you’d like. In the US, advertisers who sync their products to Google and run Google Ads <linkPmax>Performance Max campaigns</linkPmax> have seen an average of over 50% increase in clicks and over 100% increase in impressions in both their product listings and their ads on the Shopping tab. ',
						'google-listings-and-ads'
					),
					{
						linkPmax,
					}
				) }
			</p>
		),
	},
	{
		trackId: 'how-does-google-for-woocommerce-help-me-drive-sales',
		question: __(
			'How does Google for WooCommerce help me drive sales?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'With Google for WooCommerce, you can serve the best-performing ads more often, by using Google AI to pull headlines, images, product details, and more from your product feed and find more relevant customers. Your campaigns will learn and optimize in real time – to help deliver better performance and boost your ROI.',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId: 'what-are-enhanced-conversions',
		question: __(
			'What are Enhanced conversions?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'<link>Enhanced conversions</link> is a feature that can improve the accuracy of your conversion measurement and unlock more powerful bidding. It supplements your existing conversion tags by sending hashed first-party conversion data from your website to Google in a privacy-safe way.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="google-enhanced-conversions"
								href="https://support.google.com/google-ads/answer/9888656?hl=en-GB"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'which-countries-are-available-for-google-for-woocommerce',
		question: __(
			'Which countries are available for Google for WooCommerce?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ createInterpolateElement(
					__(
						'For <linkPmax>Performance Max campaigns</linkPmax>, learn more about supported countries and currencies <link>here</link>.',
						'google-listings-and-ads'
					),
					{
						linkPmax,
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="google-country-table"
								href="https://support.google.com/merchants/answer/160637#countrytable"
							/>
						),
					}
				) }
			</p>
		),
	},
	{
		trackId: 'what-is-multi-country-advertising',
		question: __(
			'What is Multi-Country Advertising?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'Multi-Country Advertising enables you to create a single Google Ads campaign that targets multiple countries at once. Google for WooCommerce automatically populates eligible countries from your Google Merchant Center account into the plug-in ads campaign creation flow.',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId:
			'can-i-enable-multi-country-advertising-on-my-existing-campaigns',
		question: __(
			'Can I enable Multi-Country Advertising on my existing campaigns?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'If you created a campaign before this feature launched, you’ll need to create a new campaign to target new countries with Multi-Country Advertising',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId: 'how-is-my-ads-budget-split-between-the-different-countries',
		question: __(
			'How is my ads budget split between the different countries?',
			'google-listings-and-ads'
		),
		answer: (
			<p>
				{ __(
					'Identify the best performing targeted countries with the help of Google AI, to make your ads reach the right shoppers at the right time.',
					'google-listings-and-ads'
				) }
			</p>
		),
	},
	{
		trackId: 'which-countries-can-i-target',
		question: __(
			'Which countries can I target?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'You can only select the countries that you’re targeting on Google Merchant Center. Your target countries must be eligible for both Google Merchant Center and Google Ads.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'To allow your products to appear in all relevant locations, make sure you’ve correctly <linkShipping>configured your shipping</linkShipping> for countries where your products can be delivered. Keep in mind that shipping services can cover multiple countries. <linkMultiCountryShipping>Learn more about multi-country shipping</linkMultiCountryShipping>.',
							'google-listings-and-ads'
						),
						{
							linkShipping: (
								<AppDocumentationLink
									context="faqs"
									linkId="google-set-up-shipping"
									href="https://support.google.com/merchants/answer/6069284"
								/>
							),
							linkMultiCountryShipping: (
								<AppDocumentationLink
									context="faqs"
									linkId="google-set-up-multi-country-shipping"
									href="https://support.google.com/merchants/answer/6069284#multicountryshipping"
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
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'free-listings', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/product-feed-information-and-free-listings/#section-1' }`.
 * @fires gla_documentation_link_click with `{ context: 'faqs', linkId: 'campaign-analytics', href: 'https://woocommerce.com/document/google-for-woocommerce/get-started/campaign-analytics' }`.
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
