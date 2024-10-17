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
} from '.~/components/google-combo-account-card/constants';

/**
 * Hook to automatically create Ads and Merchant Center accounts if they do not exist.
 *
 * @return {Object} Object containing the state of the account creation process.
 * @property {boolean} hasFinishedResolutionForExistingAdsMCAccounts Indicates whether the checks for existing Merchant Center (MC) and Google Ads accounts have been completed.
 * @property {string|null} isCreatingWhichAccount The type of account that is being created. Possible values are 'ads', 'mc', or 'both'.
 */
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

	if ( accountCreationChecksResolved ) {
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
		hasFinishedResolutionForExistingAdsMCAccounts:
			accountCreationChecksResolved,
		isCreatingWhichAccount,
	};
};

export default useAutoCreateAdsMCAccounts;
