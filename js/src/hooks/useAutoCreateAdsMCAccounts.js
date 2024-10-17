/**
 * External dependencies
 */
import { useEffect, useState, useRef, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleMCAccount from './useGoogleMCAccount';
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '.~/constants';

const useAutoCreateAdsMCAccounts = () => {
	const [ accountsState, setAccountsState ] = useState( {
		accountsCreated: false,
		isCreatingWhichAccount: null,
	} );

	const { accountsCreated, isCreatingWhichAccount } = accountsState;
	const shouldCreateAccounts = useRef();

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const {
		hasFinishedResolution: hasFinishedResolutionForGoogleAdsAccount,
		hasGoogleAdsConnection,
	} = useGoogleAdsAccount();

	const { hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount } =
		useGoogleMCAccount();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const hasExistingMCAccount = existingMCAccounts?.length > 0;
	const hasExistingAdsAccount =
		hasGoogleAdsConnection || existingAdsAccounts?.length > 0;

	const googleAdsAccountChecksResolved =
		hasFinishedResolutionForExistingAdsAccount &&
		hasFinishedResolutionForGoogleAdsAccount;

	const googleMCAccountChecksResolved =
		hasFinishedResolutionForGoogleMCAccount &&
		hasFinishedResolutionForExistingMCAccounts;

	const accountCreationChecksResolved =
		googleAdsAccountChecksResolved && googleMCAccountChecksResolved;

	if ( googleAdsAccountChecksResolved && googleMCAccountChecksResolved ) {
		if ( ! hasExistingAdsAccount || ! hasExistingMCAccount ) {
			const createBothAccounts =
				! hasExistingAdsAccount && ! hasExistingMCAccount;

			if ( createBothAccounts ) {
				shouldCreateAccounts.current = CREATING_BOTH_ACCOUNTS;
			} else if ( ! hasExistingAdsAccount ) {
				shouldCreateAccounts.current = CREATING_ADS_ACCOUNT;
			} else {
				shouldCreateAccounts.current = CREATING_MC_ACCOUNT;
			}
		}
	}

	const handlePostAccountCreation = useCallback( () => {
		if ( ! isCreatingWhichAccount ) {
			return;
		}

		const mcAccountCreated = [ 200, 403, 406, 503 ].includes(
			response?.status
		);

		const resetState =
			( isCreatingWhichAccount === CREATING_ADS_ACCOUNT && ! loading ) ||
			( isCreatingWhichAccount === CREATING_MC_ACCOUNT &&
				mcAccountCreated ) ||
			( isCreatingWhichAccount === CREATING_BOTH_ACCOUNTS &&
				mcAccountCreated &&
				! loading );

		if ( resetState ) {
			shouldCreateAccounts.current = null;
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingWhichAccount: null,
				accountsCreated: true,
			} ) );
		}
	}, [ response, loading, isCreatingWhichAccount ] );

	const handleAccountCreation = useCallback( async () => {
		if (
			! accountCreationChecksResolved ||
			isCreatingWhichAccount ||
			accountsCreated
		) {
			return;
		}

		if ( shouldCreateAccounts.current ) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingWhichAccount: shouldCreateAccounts.current,
			} ) );

			if ( shouldCreateAccounts.current === CREATING_BOTH_ACCOUNTS ) {
				await handleCreateAccount();
				await upsertAdsAccount();
			} else if (
				shouldCreateAccounts.current === CREATING_ADS_ACCOUNT
			) {
				await upsertAdsAccount();
			} else {
				await handleCreateAccount();
			}
		}
	}, [
		accountCreationChecksResolved,
		isCreatingWhichAccount,
		accountsCreated,
		handleCreateAccount,
		upsertAdsAccount,
	] );

	useEffect( () => {
		handlePostAccountCreation();
	}, [ response, loading, handlePostAccountCreation ] );

	useEffect( () => {
		handleAccountCreation();
	}, [ handleAccountCreation ] );

	return {
		accountCreationChecksResolved,
		isCreatingWhichAccount,
	};
};

export default useAutoCreateAdsMCAccounts;
