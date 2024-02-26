/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CAMPAIGN_TYPE_PMAX, glaData } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversionFooter from './enhanced-conversion/footer';
import GoogleCreditsFooter from './google-credits/footer';

const DynamicScreenFooter = ( { handleGuideFinish } ) => {
	const { data: campaigns, loading: adsCampaignsLoading } = useAdsCampaigns();

	const pmaxCampaigns = campaigns?.filter(
		( { type } ) => type === CAMPAIGN_TYPE_PMAX
	);

	if ( adsCampaignsLoading ) {
		return (
			<Fragment>
				<div className="gla-submission-success-guide__space_holder" />
				<LoadingLabel />
			</Fragment>
		);
	}

	if ( pmaxCampaigns?.length && glaData.adsConnected ) {
		return (
			<EnhancedConversionFooter handleGuideFinish={ handleGuideFinish } />
		);
	}

	return <GoogleCreditsFooter handleGuideFinish={ handleGuideFinish } />;
};

export default DynamicScreenFooter;
