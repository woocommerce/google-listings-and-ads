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
];

/**
 * @fires gla_faq with `{ context: 'setup-ads-only', id: 'why-do-i-need-a-wp-account', action: 'expand' }`.
 * @fires gla_faq with `{ context: 'setup-ads-only', id: 'why-do-i-need-a-wp-account', action: 'collapse' }`.
 */
const Faqs = () => {
	return (
		<FaqsPanel
			context="setup-ads-only"
			faqItems={ faqItems }
			trackName="gla_faq"
		/>
	);
};

export default Faqs;
