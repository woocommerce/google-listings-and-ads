/**
 * Internal dependencies
 */
import useGoogleAdsAccount from './useGoogleMCAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleMCAccounts';

/**
 * Hook to determine if a Google Ads account should be created.
 * This is based on whether the user has any existing Google Ads accounts and if they have a connection to Google Ads.
 * If the user has no existing accounts and no connection, a new ads account should be created.
 * @return {boolean} Whether a new Google Ads account should be created.
 */
const useShouldCreateAdsAccount = () => {
	const {
		hasFinishedResolution: hasResolvedAccount,
		hasGoogleAdsConnection: hasConnection,
	} = useGoogleAdsAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		data: accounts,
	} = useExistingGoogleAdsAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasResolvedAccount || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && accounts.length === 0;
};

export default useShouldCreateAdsAccount;
