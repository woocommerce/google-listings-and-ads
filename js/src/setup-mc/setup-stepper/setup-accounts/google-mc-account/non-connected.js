/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import SpinnerCard from '.~/components/spinner-card';
import NonConnectedWithState from './non-connected-with-state';

const NonConnected = () => {
	const {
		data: existingAccounts,
		hasFinishedResolution,
	} = useExistingGoogleMCAccounts();

	if ( ! hasFinishedResolution ) {
		return <SpinnerCard />;
	}

	return <NonConnectedWithState existingAccounts={ existingAccounts } />;
};

export default NonConnected;
