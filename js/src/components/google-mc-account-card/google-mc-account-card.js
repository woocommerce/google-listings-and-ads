/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import ConnectedGoogleMCAccountCard from './connected-google-mc-account-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';

const GoogleMCAccountCard = () => {
	const { hasFinishedResolution, isPreconditionReady, googleMCAccount } =
		useGoogleMCAccount();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( ! isPreconditionReady ) {
		return <DisabledCard />;
	}

	if (
		googleMCAccount.id === 0 ||
		( googleMCAccount.status !== 'connected' &&
			googleMCAccount.step !== 'link_ads' )
	) {
		return <NonConnected />;
	}

	return (
		<ConnectedGoogleMCAccountCard
			hideNotificationService
			googleMCAccount={ googleMCAccount }
		/>
	);
};

export default GoogleMCAccountCard;
