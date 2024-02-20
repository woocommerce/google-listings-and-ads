/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS, CAMPAIGN_TYPE_PMAX } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import GuidePageContent from '.~/components/guide-page-content';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import EnhancedConversion from './enhanced-conversion';
import GoogleCredits from './google-credits';

const DynamicScreenContent = () => {
	const { data: campaigns, loading: adsCampaignsLoading } = useAdsCampaigns();
	const { googleAdsAccount, isResolving: googleAdsAccountLoading } =
		useGoogleAdsAccount();

	const pmaxCampaigns = campaigns?.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);

	if ( adsCampaignsLoading || googleAdsAccountLoading ) {
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

	if (
		pmaxCampaigns?.length &&
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED
	) {
		return <EnhancedConversion />;
	}

	return <GoogleCredits />;
};

export default DynamicScreenContent;
