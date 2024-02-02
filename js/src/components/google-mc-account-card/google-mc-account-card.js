/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ConnectedGoogleMCAccountCard from './connected-google-mc-account-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

const GoogleMCAccountCard = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasFinishedResolution, isPreconditionReady, googleMCAccount } =
		useGoogleMCAccount();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( ! isPreconditionReady || ! googleAdsAccount ) {
		return <DisabledCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	return <ConnectedGoogleMCAccountCard googleMCAccount={ googleMCAccount } />;
};

export default GoogleMCAccountCard;
