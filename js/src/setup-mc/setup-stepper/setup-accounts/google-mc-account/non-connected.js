/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import SpinnerCard from '.~/components/spinner-card';
import ConnectMC from './connect-mc';
import CreateAccount from './create-account';

const NonConnected = () => {
	const {
		data: existingAccounts,
		hasFinishedResolution,
		invalidateResolution,
	} = useExistingGoogleMCAccounts();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts.length > 0 ) {
		return <ConnectMC />;
	}

	return <CreateAccount onShowExisting={ invalidateResolution } />;
};

export default NonConnected;
