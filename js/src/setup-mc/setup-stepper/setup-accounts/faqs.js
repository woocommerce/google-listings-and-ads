/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import FaqsPanel from '.~/components/faqs-panel';
import AppDocumentationLink from '.~/components/app-documentation-link';

const faqItems = [
	{
		trackId: 'why-do-i-need-a-wp-account',
		question: __(
			'Why do I need a WordPress.com account?',
			'google-listings-and-ads'
		),
		answer: __(
			'We use a WordPress.com account to connect your site to the WooCommerce and Google servers. It ensures that requests (e.g. product feed, clicks, sales, etc) from your site are securely and correctly attributed to your store. It enables a connection to your self-hosted site, and provides a common authentication interface across disparate server configurations and architectures.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'why-do-i-need-a-google-mc-account',
		question: __(
			'Why do I need a Google Merchant Center account?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<p>
					{ __(
						'Google Merchant Center helps you sync your store and product data with Google and makes the information available for both free listings on the Shopping tab and Google Shopping Ads. That means everything about your stores and products is available to shoppers when they search on a Google property.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ createInterpolateElement(
						__(
							'If you create a new Merchant Center account through this application, it will be associated with Google’s Comparison Shopping Service (Google Shopping) by default. You can change the CSS associated with your account at any time. <link>Please find more information here</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="faqs"
									linkId="find-a-partner"
									href="https://comparisonshoppingpartners.withgoogle.com/find_a_partner/"
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
 * Clicking on faq items to collapse or expand it in the Setup Merchant Center page
 *
 * @event gla_setup_mc_faq
 * @property {string} id FAQ identifier
 * @property {string} action (`expand`|`collapse`)
 */

/**
 * @fires gla_setup_mc_faq
 */
export default function Faqs() {
	return (
		<Section>
			<FaqsPanel trackName="gla_setup_mc_faq" faqItems={ faqItems } />
		</Section>
	);
}
