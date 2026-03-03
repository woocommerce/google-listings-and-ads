/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FaqsPanel from '~/components/faqs-panel';

const faqItems = [
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
	return (
		<FaqsPanel
			trackName="gla_setup_ads_faq"
			context="setup-ads"
			faqItems={ faqItems }
		/>
	);
};

export default Faqs;
