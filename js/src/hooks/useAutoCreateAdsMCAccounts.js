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

/**
 * Custom hook to handle the creation of MC and Ads accounts.
 *
 * @return {Object} Object with the following properties:
 * 	  - isCreatingAccounts: Indicates if the accounts are being created.
 *    - accountCreationChecksResolved: Indicates if the account creation checks have been resolved.
 *    - accountsCreated: Indicates if the accounts have been created
 */
const useAutoCreateAdsMCAccounts = () => {
	/**
	 * Refs are used to avoid the re-render of the parent component.
	 *
	 * accountCreationChecksResolvedRef - Indicates if the account creation checks have been resolved.
	 * isCreatingAccountsRef - Indicates if the accounts are being created.
	 * accountsCreatedRef - Indicates if the accounts have been created.
	 */
	const accountCreationChecksResolvedRef = useRef( false );
	const isCreatingAccountsRef = useRef( false );
	const isCreatingAdsAccountsRef = useRef( false );
	const isCreatingMCAccountsRef = useRef( false );
	const accountsCreatedRef = useRef( false );

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccount,
		isResolving: isResolvingExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const createAccounts = async () => {
		const hasExistingMCAccount = existingMCAccounts.length > 0;
		const hasExistingAdsAccount = existingAdsAccount.length > 0;

		isCreatingMCAccountsRef.current = ! hasExistingMCAccount;
		isCreatingAdsAccountsRef.current = ! hasExistingAdsAccount;
		isCreatingAccountsRef.current =
			! hasExistingAdsAccount || ! hasExistingMCAccount;

		if ( ! hasExistingMCAccount ) {
			await handleCreateAccount();
		}

		if ( ! hasExistingAdsAccount ) {
			await upsertAdsAccount();
		}
	};

	useEffect( () => {
		if (
			isCreatingAccountsRef.current === true &&
			response?.status === 200 &&
			! loading
		) {
			isCreatingMCAccountsRef.current = false;
			isCreatingAdsAccountsRef.current = false;
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
			isCreatingAccountsRef.current === false
		) {
			const hasExistingMCAccount = existingMCAccounts?.length > 0;
			const hasExistingAdsAccount = existingAdsAccount?.length > 0;

			if ( ! hasExistingMCAccount || ! hasExistingAdsAccount ) {
				createAccounts();
			}
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [
		isResolvingExistingAdsAccount,
		hasFinishedResolutionForExistingMCAccounts,
	] );

	return {
		accountsCreated: accountsCreatedRef.current,
		accountCreationChecksResolved: accountCreationChecksResolvedRef.current,
		isCreatingAccounts: isCreatingAccountsRef.current,
		isCreatingAdsAccount: isCreatingAdsAccountsRef.current,
		isCreatingMCAccount: isCreatingMCAccountsRef.current,
	};
};

export default useAutoCreateAdsMCAccounts;
