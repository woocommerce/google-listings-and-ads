/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LoadingLabel from '.~/components/loading-label';
import GuidePageContent from '.~/components/guide-page-content';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversions from './enhanced-conversions';
import GoogleCredits from './google-credits';

const DynamicScreenContent = () => {
	const { loaded, pmaxCampaigns } = useAdsCampaigns();

	if ( ! loaded ) {
		return (
			<GuidePageContent
				title={ __( 'Please wait', 'google-listings-and-ads' ) }
			>
				<LoadingLabel />
			</GuidePageContent>
		);
	}

	if ( pmaxCampaigns.length ) {
		return <EnhancedConversions />;
	}

	return <GoogleCredits />;
};

export default DynamicScreenContent;
