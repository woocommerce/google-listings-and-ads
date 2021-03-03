/**
 * External dependencies
 */
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';

const recordToggleEvent = ( id, isOpened ) => {
	recordEvent( 'gla_get_started_faq', {
		id,
		action: isOpened ? 'expand' : 'collapse',
	} );
};

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
							'Learn more about supported countries and currencies for Smart Shopping campaigns <link>here</link>.',
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
						'If you’re running a Smart Shopping campaign, your approved products can appear on Google Search, the Shopping tab, Gmail, Youtube and the Google Display Network.',
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},
	{
		trackId: 'what-are-smart-shopping-campaigns',
		question: __(
			'What are Smart Shopping campaigns?',
			'google-listings-and-ads'
		),
		answer: __(
			'Smart Shopping campaigns are Google Ads that combine Google’s machine learning with automated bidding and ad placements to maximize conversion value and strategically display your ads to people searching for products like yours, at your given budget. The best part? You only pay when people click on your ad.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'how-much-do-smart-shopping-campaigns-cost',
		question: __(
			'How much do Smart Shopping campaigns cost?',
			'google-listings-and-ads'
		),
		answer: __(
			'Smart Shopping campaigns are pay-per-click, meaning you only pay when someone clicks on your ads. You can customize your daily budget in Google Listings & Ads but we recommend starting off with the suggested minimum budget, and you can change this budget at any time.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'can-i-run-both-free-listings-and-smart-shopping-campaigns',
		question: __(
			'Can I run both free listings and Smart Shopping campaigns at the same time?',
			'google-listings-and-ads'
		),
		answer: __(
			'Yes, you can run both at the same time, and we recommend it! In the US, advertisers running free listings and ads together have seen an average of over 50% increase in clicks and over 100% increase in impressions on both free listings and ads on the Shopping tab. Your store is automatically opted into free listings automatically and can choose to run a paid Smart Shopping campaign.',
			'google-listings-and-ads'
		),
	},
	{
		trackId: 'what-are-the-terms-and-conditions-for-ad-credit-offer',
		question: __(
			'What are the terms and conditions for the $150 ad credit offer?',
			'google-listings-and-ads'
		),
		answer: (
			<>
				{ __(
					'Terms and conditions for US-based retailers',
					'google-listings-and-ads'
				) }
				<br />
				{ createInterpolateElement(
					__(
						'Amounts differ by currencies and countries, <link>Learn about your coupon values</link>.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="faqs"
								linkId="learn-about-google-ads-coupon-values"
								href="https://support.google.com/google-ads/answer/7624810"
							/>
						),
					}
				) }
				<p>
					{ __(
						'Terms and conditions for this offer:',
						'google-listings-and-ads'
					) }
				</p>
				<ol>
					<li>
						{ __(
							'Offer available to customers with a billing address in the United States only. One promotional code per advertiser.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'To activate this offer: Click on the button or link associated with this offer before December 31, 2021 for the promotional code to be automatically applied to your account. In order to participate in this offer, the promotional code must be applied to your first Google Ads account within 14 days of your first ad impression being served from such account.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'To earn the credit: Start advertising! The advertising costs you accrue in this account in the 30 days following your entry of the promotional code, excluding any taxes, will be matched with an advertising credit in the same amount, up to a maximum value of $150.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'Once 2 and 3 are completed, the credit will typically be applied within 5 days to the Billing Summary of your account.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'Credits apply to future advertising costs only. Credits cannot be applied to costs accrued before the code was entered.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'You won’t receive a notification once your credit is used up and any additional advertising costs will be charged to your form of payment. If you don’t want to continue advertising, you can pause or delete your campaigns at any time.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ __(
							'Your account must be successfully billed by Google Ads and remain in good standing in order to qualify for the promotional credit.',
							'google-listings-and-ads'
						) }
					</li>
					<li>
						{ createInterpolateElement(
							__(
								'Full terms and conditions can be found here <link>http://www.google.com/ads/coupons/terms.html</link>.',
								'google-listings-and-ads'
							),
							{
								link: (
									<AppDocumentationLink
										context="faqs"
										linkId="terms-and-conditions-of-google-ads-coupons"
										href="http://www.google.com/ads/coupons/terms.html"
									/>
								),
							}
						) }
					</li>
				</ol>
			</>
		),
	},
];

const Faqs = () => {
	const getPanelToggleHandler = ( id ) => ( isOpened ) => {
		recordToggleEvent( id, isOpened );
	};

	return (
		<Panel
			className="gla-get-started-fags"
			header={ __(
				'Frequently asked questions',
				'google-listings-and-ads'
			) }
		>
			{ faqItems.map( ( { trackId, question, answer } ) => {
				return (
					<PanelBody
						key={ trackId }
						title={ question }
						initialOpen={ false }
						onToggle={ getPanelToggleHandler( trackId ) }
					>
						<PanelRow>{ answer }</PanelRow>
					</PanelBody>
				);
			} ) }
		</Panel>
	);
};

export default Faqs;
