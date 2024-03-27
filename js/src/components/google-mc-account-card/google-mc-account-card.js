/**
 * Internal dependencies
 */
import SpinnerCard from '.~/components/spinner-card';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import useRestAPIAuthURLRedirect from '.~/hooks/useRestAPIAuthURLRedirect';
import ConnectedGoogleMCAccountCard from './connected-google-mc-account-card';
import DisabledCard from './disabled-card';
import NonConnected from './non-connected';

const GoogleMCAccountCard = () => {
	const { hasFinishedResolution, isPreconditionReady, googleMCAccount } =
		useGoogleMCAccount();

	const [ handleRestAPIAuthURLRedirect ] = useRestAPIAuthURLRedirect();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( ! isPreconditionReady ) {
		return <DisabledCard />;
	}

	if ( googleMCAccount.id === 0 || googleMCAccount.status !== 'connected' ) {
		return <NonConnected />;
	}

	if ( googleMCAccount.wpcom_rest_api_status !== 'approved' ) {
		handleRestAPIAuthURLRedirect();
		return <SpinnerCard />;
	}

	return (
		<ConnectedGoogleMCAccountCard
			hideNotificationService
			googleMCAccount={ googleMCAccount }
		/>
	);
};

export default GoogleMCAccountCard;
