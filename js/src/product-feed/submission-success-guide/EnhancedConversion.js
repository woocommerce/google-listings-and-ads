/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import GuidePageContent from '.~/components/guide-page-content';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useGoogleAccount from '.~/hooks/useGoogleAccount';

const EnhancedConversion = () => {
	const { acceptedCustomerDataTerms: hasAcceptedTerms } =
		useAcceptedCustomerDataTerms();
	const { google } = useGoogleAccount();
	console.log( google );

	return (
		<GuidePageContent
			title={ __(
				'Optimize your conversion tracking with Enhanced Conversions',
				'google-listings-and-ads'
			) }
		>
			<p>
				{ createInterpolateElement(
					__(
						'Enhance your conversion tracking accuracy and empower your bidding strategy with our latest feature: <b>Enhanced Conversion Tracking</b>. This feature seamlessly integrates with your existing conversion tags, ensuring the secure and privacy-conscious transmission of conversion data from your website to Google',
						'google-listings-and-ads'
					),
					{
						b: <b />,
					}
				) }
			</p>

			{ hasAcceptedTerms && (
				<p>
					{ __(
						'Clicking confirm will enable Enhanced Conversions on your account and update your tags accordingly. This feature can also be managed from Google Listings & Ads > Settings',
						'google-listings-and-ads'
					) }
				</p>
			) }

			{ ! hasAcceptedTerms && (
				<p>
					{ __(
						'Activating it is easy – just agree to the terms of service on Google Ads and we will make the tagging changes needed for you. This feature can also be managed from Google Listings & Ads > Settings',
						'google-listings-and-ads'
					) }
				</p>
			) }
		</GuidePageContent>
	);
};

export default EnhancedConversion;
