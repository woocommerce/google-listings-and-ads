/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import LoadingLabel from '.~/components/loading-label';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversionFooter from './enhanced-conversion/footer';
import GoogleCreditsFooter from './google-credits/footer';

const DynamicScreenFooter = ( { onModalClose = noop } ) => {
	const { loaded, pmaxCampaigns } = useAdsCampaigns();

	if ( ! loaded ) {
		return (
			<Fragment>
				<div className="gla-submission-success-guide__space_holder" />
				<LoadingLabel />
			</Fragment>
		);
	}

	if ( pmaxCampaigns.length && glaData.adsConnected ) {
		return <EnhancedConversionFooter onModalClose={ onModalClose } />;
	}

	return <GoogleCreditsFooter onModalClose={ onModalClose } />;
};

export default DynamicScreenFooter;
