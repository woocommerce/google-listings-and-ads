/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import FaqsPanel from '.~/components/faqs-panel';
import AppDocumentationLink from '.~/components/app-documentation-link';

const faqItems = [
	{
		trackId: 'what-will-my-ads-look-like',
		question: __(
			'What will my ads look like?',
			'google-listings-and-ads'
		),
		answer: (
			<div>
				{ createInterpolateElement(
					__(
						'Google will generate text ads and responsive display ads in various combinations and formats from the headlines, images, and descriptions you add. Your ads will automatically adjust their size, appearance, and format to fit available ad spaces. <link>See common ad formats</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="assets-faq"
								linkId="assets-faq-about-ad-formats-available-in-different-campaign-types"
								href="https://support.google.com/google-ads/answer/1722124"
							/>
						),
					}
				) }
			</div>
		),
	},
	{
		trackId: 'what-makes-these-ads-different-from-product-ads',
		question: __(
			'What makes these ads different from product ads?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				<div>
					{ __(
						`Dynamic ad assets can elevate your campaign by offering a variety of ad combinations that capture your audience's attention and generate maximum engagement. By leveraging Google's asset-mixing technology, your ads can be optimized to deliver the right message, to the right people, at the right time.`,
						'google-listings-and-ads'
					) }
				</div>
				<div>
					{ __(
						'Compared to product ads—which showcase individual products and are designed to drive direct sales and revenue—responsive ads with dynamic ad assets are typically used to highlight your business, generate interest, and attract new customers. While both types of ads can drive conversions, using them together can generate even greater results.',
						'google-listings-and-ads'
					) }
				</div>
			</>
		),
	},
];

/**
 * Renders a toggleable FAQs section about the campaign assets of the Google Ads.
 *
 * @fires gla_faq with `{ context: 'campaign-management', id: 'what-will-my-ads-look-like', action: 'expand' | 'collapse' }`.
 * @fires gla_faq with `{ context: 'campaign-management', id: 'what-makes-these-ads-different-from-product-ads', action: 'expand' | 'collapse' }`.
 * @fires gla_documentation_link_click with `{ context: 'assets-faq', linkId: 'assets-faq-about-ad-formats-available-in-different-campaign-types', href: 'https://support.google.com/google-ads/answer/1722124' }`.
 */
const FaqsSection = () => {
	return (
		<Section>
			<FaqsPanel context="campaign-management" faqItems={ faqItems } />
		</Section>
	);
};

export default FaqsSection;
