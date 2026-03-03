/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import FaqsPanel from '~/components/faqs-panel';

/**
 * Clicking on faq items to collapse or expand it in the Onboarding Flow or creating/editing a campaign
 *
 * @event gla_setup_ads_faq
 * @property {string} id FAQ identifier
 * @property {string} action (`expand`|`collapse`)
 */

/**
 * Renders a toggleable FAQs about Google Ads.
 *
 * @fires gla_setup_ads_faq
 */
const Faqs = () => {
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	let faqItems = [
		{
			trackId: 'how-does-google-ads-work',
			question: __(
				'How does Google Ads work?',
				'google-listings-and-ads'
			),
			answer: __(
				'Google Ads works by displaying your ad when people search online for the products and services you offer. By leveraging smart technology, Google Ads helps get your ads in front of potential customers at just the moment they’re ready to take action.',
				'google-listings-and-ads'
			),
		},
		{
			trackId: 'what-is-a-product-feed',
			question: __(
				'What is a product feed?',
				'google-listings-and-ads'
			),
			answer: __(
				'Your product feed is the central data source that contains a list of products you want to advertise through Merchant Center. By default, Google syncs all active products from your WooCommerce inventory. You can choose to exclude products later after this setup.',
				'google-listings-and-ads'
			),
		},
		{
			trackId: 'how-much-does-google-ads-cost',
			question: __(
				'How much does Google Ads cost?',
				'google-listings-and-ads'
			),
			answer: __(
				'With Google Ads, you decide how much to spend. There’s no minimum spend, and no time commitment. Your costs may vary from day to day, but you won’t be charged more than your daily budget times the number of days in a month. You pay only for the actual clicks and calls that your ad receives.',
				'google-listings-and-ads'
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
					<div>
						{ __(
							'If you’re selling in the US, then eligible product feed can appear in search results across Google Search, Google Images, and the Google Shopping tab. If you’re selling outside the US, product feed will appear on the Shopping tab.',
							'google-listings-and-ads'
						) }
					</div>
					<div>
						{ __(
							'If you’re running a Performance Max Campaign, your approved products can appear on Google Search, Google Maps, the Shopping tab, Gmail, Youtube, the Google Display Network, and Discover feed.',
							'google-listings-and-ads'
						) }
					</div>
				</>
			),
		},
		{
			trackId: 'how-long-until-i-see-results-with-google-ads',
			question: __(
				'How long until I see results with Google Ads?',
				'google-listings-and-ads'
			),
			answer: __(
				'Google’s Performance Max campaigns are powered by machine learning models. These models train and adapt based on the data you provide in your campaign. This means performance optimization can take time. Typically, this learning process takes 1—2 weeks.',
				'google-listings-and-ads'
			),
		},
	];

	if ( ! hasGoogleMCConnection ) {
		faqItems = [
			{
				trackId: 'where-will-my-services-appear',
				question: __(
					'Where will my services appear?',
					'google-listings-and-ads'
				),
				answer: __(
					'If you’re running a Performance Max Campaign, your services can appear on Google Search, Google Maps, Gmail, Youtube, the Google Display Network, and Discover feed. This will only be applicable in the locations your services are offered.',
					'google-listings-and-ads'
				),
			},
			{
				trackId: 'what-is-pmax-campaign-for-services',
				question: __(
					'What is a PMax campaign for services?',
					'google-listings-and-ads'
				),
				answer: __(
					"PMax campaign for services uses your website's landing pages and creative assets (images, videos, text) to find leads across Search, YouTube, Display, and Maps.",
					'google-listings-and-ads'
				),
			},
			{
				trackId: 'can-i-use-this-without-products',
				question: __(
					"I don't have physical products. Can I still use this?",
					'google-listings-and-ads'
				),
				answer: __(
					'Yes! It’s designed specifically for service providers ({give_examples}, etc.). It treats your "Services" as the offer and optimizes for leads or appointments.',
					'google-listings-and-ads'
				),
			},
			{
				trackId: 'what-makes-these-ads-different-from-product-ads',
				question: __(
					'What makes these ads different from product ads?',
					'google-listings-and-ads'
				),
				answer: __(
					'Product ads are focused on a tangible product being sold. These ads are for services-based businesses largely for lead generation (i.e. appointment booking). Online sales (non-feed) is also a possible objective.',
					'google-listings-and-ads'
				),
			},
		];
	}

	return (
		<FaqsPanel
			trackName="gla_setup_ads_faq"
			context="setup-ads"
			faqItems={ faqItems }
		/>
	);
};

export default Faqs;
