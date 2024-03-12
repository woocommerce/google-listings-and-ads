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
	const { isResolving, isPreconditionReady, googleMCAccount } =
		useGoogleMCAccount();
	const { googleAdsAccount, isResolving: isResolvingGoogleAdsAccount } =
		useGoogleAdsAccount();

	if ( isResolving || isResolvingGoogleAdsAccount ) {
		return <SpinnerCard />;
	}

	if (
		! isPreconditionReady ||
		! googleAdsAccount ||
		googleAdsAccount.status !== GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED
	) {
		return <DisabledCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	return <ConnectedGoogleMCAccountCard googleMCAccount={ googleMCAccount } />;
};

export default GoogleMCAccountCard;
