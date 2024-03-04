/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversionFooter from './enhanced-conversion/footer';
import GoogleCreditsFooter from './google-credits/footer';

const DynamicScreenFooter = ( { onClose } ) => {
	const { loaded, pmaxCampaigns } = useAdsCampaigns();

	if ( ! loaded ) {
		return (
			<Fragment>
				<div className="gla-submission-success-guide__space_holder" />
				<LoadingLabel />
			</Fragment>
		);
	}

	if ( pmaxCampaigns.length && glaData.initialWpData.adsId ) {
		return <EnhancedConversionFooter onClose={ onClose } />;
	}

	return <GoogleCreditsFooter onClose={ onClose } />;
};

export default DynamicScreenFooter;
