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
 * useAccountsData hook.
 *
 * Checks if accounts are being created and if they are ready.
 * @return {Object} Object with account data.
 * @property {boolean} creatingAccounts - Whether accounts are being created.
 * @property {Object} google - Google account data.
 * @property {Object} googleAdsAccount - Google Ads account data.
 * @property {Object} googleMCAccount - Google Merchant Center account data.
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
