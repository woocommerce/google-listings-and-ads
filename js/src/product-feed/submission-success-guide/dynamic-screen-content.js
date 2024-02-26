/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CAMPAIGN_TYPE_PMAX, glaData } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import GuidePageContent from '.~/components/guide-page-content';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversion from './enhanced-conversion';
import GoogleCredits from './google-credits';

const DynamicScreenContent = () => {
	const { data: campaigns, loading: adsCampaignsLoading } = useAdsCampaigns();

	const pmaxCampaigns = campaigns?.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);

	if ( adsCampaignsLoading ) {
		return (
			<GuidePageContent
				title={ __( 'Please wait', 'google-listings-and-ads' ) }
			>
				<div className="screen-content--loading">
					<LoadingLabel />
				</div>
			</GuidePageContent>
		);
	}

	if ( pmaxCampaigns?.length && glaData.adsConnected ) {
		return <EnhancedConversion />;
	}

	return <GoogleCredits />;
};

export default DynamicScreenContent;
