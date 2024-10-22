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

const useAccountCreationData = () => {
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
	const { creatingAccounts, accountsCreated } = accountCreationContext;

	const accountDetailsResolved =
		hasFinishedResolutionForGoogleAccount &&
		hasFinishedResolutionForGoogleAdsAccount &&
		hasFinishedResolutionForGoogleMCAccount;

	useEffect( () => {
		if ( creatingAccounts ) {
			wasCreatingAccounts.current = creatingAccounts;
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
		creatingAccounts,
		accountsCreated,
		accountDetailsResolved,
		google.email,
		googleAdsAccount.id,
		googleMCAccount.id,
	] );

	return {
		creatingAccounts: wasCreatingAccounts.current,
		email: google.email,
		googleAdsAccount,
		googleMCAccount,
	};
};

export default useAccountCreationData;
