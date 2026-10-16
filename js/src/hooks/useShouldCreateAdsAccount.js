/**
 * Internal dependencies
 */
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';

/**
 * Determines whether a Google Ads account should be created.
 *
 * @return {boolean|null} True if an Ads account should be created, false if not, or null if still determining.
 */
const useShouldCreateAdsAccount = () => {
	const {
		hasFinishedResolution: hasResolvedAccount,
		hasGoogleAdsConnection: hasConnection,
	} = useGoogleAdsAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		existingAccounts: accounts,
	} = useExistingGoogleAdsAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasResolvedAccount || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && accounts?.length === 0;
};

export default useShouldCreateAdsAccount;
