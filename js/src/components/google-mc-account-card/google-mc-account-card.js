/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedCard from './connected-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';

const GoogleMCAccountCard = () => {
	const { hasFinishedResolution, googleMCAccount } = useGoogleMCAccount();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	/**
	 * If there is no googleMCAccount, this means users have not connected their Google account,
	 * or have not granted necessary access permissions for Google Merchant Center,
	 * so we show a DisabledCard here.
	 */
	if ( ! googleMCAccount ) {
		return <DisabledCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	return <ConnectedCard googleMCAccount={ googleMCAccount } />;
};

export default GoogleMCAccountCard;
