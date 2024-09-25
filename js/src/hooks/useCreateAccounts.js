/* eslint-disable react-hooks/exhaustive-deps */
/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../components/google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

/**
 * Custom hook to handle the creation of MC and Ads accounts.
 */
const useCreateAccounts = () => {
	const accountCreationChecksResolvedRef = useRef( false );
	const isCreatingAccountsRef = useRef( false );
	const accountsCreatedRef = useRef( false );

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsccount,
	} = useGoogleAdsAccount();

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccount,
		isResolving: isResolvingExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const [ handleCreateAccount, { data: account, response } ] =
		useCreateMCAccount();

	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	// Process account creation completion.
	useEffect( () => {
		if ( response?.status === 200 && ! loading ) {
			isCreatingAccountsRef.current = false;
			accountsCreatedRef.current = true;
		}
	}, [ response, loading ] );

	useEffect( () => {
		const existingAccountsResolved =
			! isResolvingExistingAdsAccount &&
			hasFinishedResolutionForExistingMCAccounts;

		accountCreationChecksResolvedRef.current = existingAccountsResolved;

		if (
			existingAccountsResolved &&
			isCreatingAccountsRef.current === false &&
			account === undefined &&
			hasFinishedResolutionForExistingAdsccount &&
			googleAdsAccount.id === 0
		) {
			const hasExistingAccounts =
				existingMCAccounts?.length > 0 &&
				existingAdsAccount?.length > 0;

			if ( ! hasExistingAccounts ) {
				const createAccounts = async () => {
					await handleCreateAccount();
					await upsertAdsAccount();
				};

				isCreatingAccountsRef.current = true;
				accountsCreatedRef.current = false;
				createAccounts();
			}
		}
	}, [
		isResolvingExistingAdsAccount,
		hasFinishedResolutionForExistingMCAccounts,
	] );

	return {
		isCreatingAccounts: isCreatingAccountsRef.current,
		accountCreationChecksResolved: accountCreationChecksResolvedRef.current,
		accountsCreated: accountsCreatedRef.current,
	};
};

export default useCreateAccounts;
