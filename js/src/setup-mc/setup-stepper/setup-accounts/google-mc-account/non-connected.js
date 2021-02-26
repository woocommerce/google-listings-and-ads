/**
 * Internal dependencies
 */
import ConnectMCCard from './connect-mc-card';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import SpinnerCard from './spinner-card';
import CreateAccount from './create-account';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleMCAccounts();

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts.length === 0 ) {
		return <CreateAccount />;
	}

	return <ConnectMCCard />;
};

export default NonConnected;
