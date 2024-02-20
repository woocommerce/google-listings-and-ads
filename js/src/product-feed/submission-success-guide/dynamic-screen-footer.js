/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GOOGLE_ADS_ACCOUNT_STATUS, CAMPAIGN_TYPE_PMAX } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import EnhancedConversionFooter from './enhanced-conversion/footer';
import GoogleCreditsFooter from './google-credits/footer';

const DynamicScreenFooter = ( { handleGuideFinish } ) => {
	const { data: campaigns, loading: adsCampaignsLoading } = useAdsCampaigns();
	const { googleAdsAccount, isResolving: googleAdsAccountLoading } =
		useGoogleAdsAccount();

	const pmaxCampaigns = campaigns?.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);

	if ( adsCampaignsLoading || googleAdsAccountLoading ) {
		return (
			<Fragment>
				<div className="gla-submission-success-guide__space_holder" />
				<LoadingLabel />
			</Fragment>
		);
	}

	if (
		pmaxCampaigns?.length &&
		googleAdsAccount?.status === GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED
	) {
		return <EnhancedConversionFooter />;
	}

	return <GoogleCreditsFooter handleGuideFinish={ handleGuideFinish } />;
};

export default DynamicScreenFooter;
