/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';

const GoogleCredits = () => {
	return (
		<GuidePageContent
			title={ __(
				'Spend $500 to get $500 in Google Ads credits',
				'google-listings-and-ads'
			) }
		>
			<p>
				{ __(
					'New to Google Ads? Get $500 in ad credit when you spend $500 within your first 60 days* You can edit or cancel your campaign at any time.',
					'google-listings-and-ads'
				) }
			</p>
			<cite>
				{ createInterpolateElement(
					__(
						'*Full terms and conditions <link>here</link>.',
						'google-listings-and-ads'
					),
					{
						link: (
							<ContentLink
								href="https://www.google.com/ads/coupons/terms/"
								context="terms-of-ads-coupons"
							/>
						),
					}
				) }
			</cite>
		</GuidePageContent>
	);
};

export default GoogleCredits;
