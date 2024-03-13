/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import GuidePageContent from '.~/components/guide-page-content';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversion from './enhanced-conversion';
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

	if ( pmaxCampaigns.length && glaData.adsConnected ) {
		return <EnhancedConversion />;
	}

	return <GoogleCredits />;
};

export default DynamicScreenContent;
