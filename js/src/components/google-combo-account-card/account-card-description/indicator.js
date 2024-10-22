/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ConnectedIconLabel from '.~/components/connected-icon-label';
import LoadingLabel from '.~/components/loading-label/loading-label';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccountReady from '.~/hooks/useGoogleAdsAccountReady';

/**
 * Account creation indicator.
 * Displays a loading indicator when accounts are being created or a connected icon when accounts are connected.
 * @param {Object} props Component props.
 * @param {string|null} props.creatingWhich Whether the accounts are being created. Possible values are: 'both', 'ads', 'mc'.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = ( { creatingWhich } ) => {
	const { googleMCAccount, isPreconditionReady } = useGoogleMCAccount();
	const isGoogleAdsReady = useGoogleAdsAccountReady();

	const isGoogleMCConnected =
		isPreconditionReady &&
		( googleMCAccount?.status === 'connected' ||
			( googleMCAccount?.status === 'incomplete' &&
				googleMCAccount?.step === 'link_ads' ) );

	if ( creatingWhich ) {
		return (
			<LoadingLabel
				text={ __( 'Creating…', 'google-listings-and-ads' ) }
			/>
		);
	}

	if ( isGoogleAdsReady && isGoogleMCConnected ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
