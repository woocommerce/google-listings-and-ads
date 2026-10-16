/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '~/components/connected-icon-label';
import LoadingLabel from '~/components/loading-label';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';

/**
 * Account creation indicator.
 * Displays a loading indicator when an account is being created or a connected icon when an Ads account is connected.
 * @param {Object} props Component props.
 * @param {boolean} props.showSpinner Whether to display a spinner.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = ( { showSpinner } ) => {
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();

	if ( showSpinner ) {
		return (
			<LoadingLabel
				text={ __( 'Creating…', 'google-listings-and-ads' ) }
			/>
		);
	}

	if ( isGoogleAdsReady ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
