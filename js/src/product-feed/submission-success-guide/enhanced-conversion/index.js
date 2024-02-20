/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import LoadingLabel from '.~/components/loading-label';
import GuidePageContent from '.~/components/guide-page-content';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const EnhancedConversion = () => {
	const {
		acceptedCustomerDataTerms: hasAcceptedTerms,
		hasFinishedResolution,
	} = useAcceptedCustomerDataTerms();

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
						'Enhance your conversion tracking accuracy and empower your bidding strategy with our latest feature: <strong>Enhanced Conversion Tracking</strong>. This feature seamlessly integrates with your existing conversion tags, ensuring the secure and privacy-conscious transmission of conversion data from your website to Google.',
						'google-listings-and-ads'
					),
					{
						strong: <strong />,
					}
				) }
			</p>

			{ ! hasFinishedResolution && (
				<LoadingLabel
					text={ __( 'Please wait…', 'google-listings-and-ads' ) }
				/>
			) }

			{ hasAcceptedTerms === true && hasFinishedResolution && (
				<p>
					{ __(
						'Clicking confirm will enable Enhanced Conversions on your account and update your tags accordingly. This feature can also be managed from Google Listings & Ads > Settings',
						'google-listings-and-ads'
					) }
				</p>
			) }

			{ hasAcceptedTerms === false && hasFinishedResolution && (
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
