/**
 * Internal dependencies
 */
import useGoogleMCAccount from './useGoogleMCAccount';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';

/**
 * Hook to determine if a Merchant Center account should be created.
 * This is based on whether the user has any existing Merchant Center accounts and if they have a connection to Merchant Center.
 * If the user has no existing accounts and no connection, a new account should be created.
 * @return {boolean} Whether a new Merchant Center account should be created.
 */
const useShouldCreateMCAccount = () => {
	const {
		hasFinishedResolution: hasResolvedAccount,
		hasGoogleMCConnection: hasConnection,
	} = useGoogleMCAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		data: accounts,
	} = useExistingGoogleMCAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasResolvedAccount || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && accounts.length === 0;
};

export default useShouldCreateMCAccount;
