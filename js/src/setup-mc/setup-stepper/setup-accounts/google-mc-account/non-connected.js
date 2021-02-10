/**
 * Internal dependencies
 */
import CreateAccountCard from './create-account-card';
import ConnectMCCard from './connect-mc-card';
import useExistingGoogleMCAccounts from '../../../../hooks/useExistingGoogleMCAccounts';
import SpinnerCard from './spinner-card';

const NonConnected = () => {
	const { existingAccounts } = useExistingGoogleMCAccounts();

	if ( ! existingAccounts ) {
		return <SpinnerCard />;
	}

	if ( existingAccounts.length === 0 ) {
		return <CreateAccountCard />;
	}

	return <ConnectMCCard />;
};

export default NonConnected;
