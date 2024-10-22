/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import { useAccountCreationContext } from '.~/components/google-combo-account-card/account-creation-context';

/**
 * @typedef {Object} AccountsData
 * @property {('ads'|'mc'|'both'|null)} creatingWhich - The type of accounts being created, or `null` if none.
 * @property {Object} google - Google account data.
 * @property {string} google.email - Google account email.
 * @property {Object} googleAdsAccount - Google Ads account data.
 * @property {number} googleAdsAccount.id - Google Ads account ID.
 * @property {Object} googleMCAccount - Google Merchant Center account data.
 * @property {number} googleMCAccount.id - Google Merchant Center account ID.
 */

/**
 * useAccountsData hook.
 *
 * Checks if accounts are being created and if they are ready.
 * @return {AccountsData} Object with account data.
 */
const useAccountsData = () => {
	const {
		google,
		hasFinishedResolution: hasFinishedResolutionForGoogleAccount,
	} = useGoogleAccount();
	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForGoogleAdsAccount,
	} = useGoogleAdsAccount();
	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount,
	} = useGoogleMCAccount();

	const accountCreationContext = useAccountCreationContext();
	const wasCreatingAccounts = useRef( null );
	const { creatingWhich, accountsCreated } = accountCreationContext;

	const accountDetailsResolved =
		hasFinishedResolutionForGoogleAccount &&
		hasFinishedResolutionForGoogleAdsAccount &&
		hasFinishedResolutionForGoogleMCAccount;

	useEffect( () => {
		if ( creatingWhich ) {
			wasCreatingAccounts.current = creatingWhich;
		}

		if (
			wasCreatingAccounts.current &&
			accountsCreated &&
			accountDetailsResolved
		) {
			const accountsReady =
				google.email && !! googleAdsAccount.id && !! googleMCAccount.id;

			if ( accountsReady ) {
				wasCreatingAccounts.current = null;
			}
		}
	}, [
		creatingWhich,
		accountsCreated,
		accountDetailsResolved,
		google,
		googleAdsAccount,
		googleMCAccount,
	] );

	return {
		creatingWhich: wasCreatingAccounts.current,
		google,
		googleAdsAccount,
		googleMCAccount,
	};
};

export default useAccountsData;
