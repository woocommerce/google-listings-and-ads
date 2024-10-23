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
 * @param {boolean} props.creatingAccounts Whether the accounts are being created.
 * @return {JSX.Element|null} Indicator component.
 */
const Indicator = ( { creatingAccounts } ) => {
	const { googleMCAccount, isPreconditionReady } = useGoogleMCAccount();
	const isGoogleAdsConnected = useGoogleAdsAccountReady();

	const isGoogleMCConnected =
		isPreconditionReady &&
		( googleMCAccount?.status === 'connected' ||
			( googleMCAccount?.status === 'incomplete' &&
				googleMCAccount?.step === 'link_ads' ) );

	if ( creatingAccounts ) {
		return (
			<LoadingLabel
				text={ __( 'Creating…', 'google-listings-and-ads' ) }
			/>
		);
	}

	if ( isGoogleAdsConnected && isGoogleMCConnected ) {
		return <ConnectedIconLabel />;
	}

	return null;
};

export default Indicator;
