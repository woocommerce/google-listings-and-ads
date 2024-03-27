/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import LoadingLabel from '.~/components/loading-label';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import EnhancedConversionActions from './enhanced-conversion/actions';
import GoogleCreditsActions from './google-credits/actions';

const DynamicScreenActions = ( { onModalClose = noop } ) => {
	const { loaded, pmaxCampaigns } = useAdsCampaigns();

	if ( ! loaded ) {
		return (
			<Fragment>
				<div className="gla-submission-success-guide__space_holder" />
				<LoadingLabel />
			</Fragment>
		);
	}

	if ( pmaxCampaigns.length ) {
		return <EnhancedConversionActions onModalClose={ onModalClose } />;
	}

	return <GoogleCreditsActions onModalClose={ onModalClose } />;
};

export default DynamicScreenActions;
