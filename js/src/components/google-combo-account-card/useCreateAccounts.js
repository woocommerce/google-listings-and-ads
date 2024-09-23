/* eslint-disable react-hooks/exhaustive-deps */
/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import { receiveMCAccount } from '.~/data/actions';
import { useAppDispatch } from '.~/data';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

/**
 * Custom hook to handle the creation of MC and Ads accounts.
 */
const useCreateAccounts = () => {
	const accountCreationResolvedRef = useRef( false );
	const isCreatingAccountsRef = useRef( false );

	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsccounts,
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
	const { invalidateResolution } = useAppDispatch();

	// Process account creation completion.
	useEffect( () => {
		if ( response?.status === 200 && ! loading ) {
			receiveMCAccount( account );
			invalidateResolution( 'getExistingGoogleAdsAccounts' );
			isCreatingAccountsRef.current = false;
		}
	}, [ response, loading ] );

	useEffect( () => {
		if (
			! isResolvingExistingAdsAccount &&
			hasFinishedResolutionForExistingMCAccounts &&
			isCreatingAccountsRef.current === false &&
			accountCreationResolvedRef.current === false &&
			account === undefined
		) {
			accountCreationResolvedRef.current = true;
			const hasExistingAccounts = true;

			if ( ! hasExistingAccounts ) {
				const createAccounts = async () => {
					await handleCreateAccount();
					await upsertAdsAccount();
				};

				isCreatingAccountsRef.current = true;
				createAccounts();
			}
		}
	}, [
		isResolvingExistingAdsAccount,
		hasFinishedResolutionForExistingMCAccounts,
	] );

	return {
		isCreatingAccounts: isCreatingAccountsRef.current,
		accountCreationResolved: accountCreationResolvedRef.current,
	};
};

export default useCreateAccounts;
