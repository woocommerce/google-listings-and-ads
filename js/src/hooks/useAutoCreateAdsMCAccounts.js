/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleMCAccount from './useGoogleMCAccount';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

/**
 * @typedef {Object} AutoCreateAccountsStatus
 * @property {boolean} accountsCreated Indicates if both the Google Ads and Google Merchant Center accounts have been successfully created.
 * @property {boolean} accountCreationChecksResolved Indicates if the account creation checks (for existing accounts) have been resolved.
 * @property {boolean} isCreatingAccounts Indicates if the Google Ads and/or Google Merchant Center account(s) are being created.
 * @property {boolean} isCreatingOnlyAdsAccount Indicates if only the Google Ads account is currently being created.
 * @property {boolean} isCreatingBothAccounts Indicates if both the Google Ads and Google Merchant Center accounts are currently being created.
 * @property {boolean} isCreatingOnlyMCAccount Indicates if only the Google Merchant Center account is currently being created.
 * @property {boolean} hasExistingMCAccount Indicates if the user has existing Google Merchant Center account(s).
 */

/**
 * Custom hook to handle the creation of Google Merchant Center (MC) and Google Ads accounts.
 * @param {Object} createMCAccount Result of the useCreateMCAccount hook from the parent component.
 * @return {AutoCreateAccountsStatus} Object containing properties related to the account creation status.
 */
const useAutoCreateAdsMCAccounts = ( createMCAccount ) => {
	const [ accountsState, setAccountsState ] = useState( {
		isCreatingBothAccounts: false,
		isCreatingAdsAccount: false,
		isCreatingMCAccount: false,
		accountsCreated: false,
	} );
	const {
		isCreatingBothAccounts,
		isCreatingAdsAccount,
		isCreatingMCAccount,
		accountsCreated,
	} = accountsState;

	const initHasExistingMCAccountsRef = useRef( null );
	const initHasExistingAdsAccountsRef = useRef( null );

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
	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount,
	} = useGoogleMCAccount();

	const [ handleCreateAccount, { response } ] = createMCAccount;
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const isGoogleMCConnected = [
		GOOGLE_MC_ACCOUNT_STATUS.CONNECTED,
		GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE,
	].includes( googleMCAccount?.status );
	const hasExistingMCAccount =
		isGoogleMCConnected || existingMCAccounts?.length > 0;
	const hasExistingAdsAccount =
		hasGoogleAdsConnection || existingAdsAccounts?.length > 0;

	if (
		initHasExistingMCAccountsRef.current === null &&
		hasFinishedResolutionForExistingMCAccounts &&
		hasFinishedResolutionForGoogleMCAccount
	) {
		initHasExistingMCAccountsRef.current = hasExistingMCAccount;
	}

	if (
		initHasExistingAdsAccountsRef.current === null &&
		hasFinishedResolutionForExistingAdsAccount &&
		hasFinishedResolutionForGoogleAdsAccount
	) {
		initHasExistingAdsAccountsRef.current = hasExistingAdsAccount;
	}

	const accountCreationChecksResolved =
		initHasExistingAdsAccountsRef.current !== null &&
		initHasExistingMCAccountsRef.current !== null;
	const shouldCreateAdsAccount =
		initHasExistingAdsAccountsRef.current === false &&
		initHasExistingMCAccountsRef.current === true;
	const shouldCreateMCAccount =
		initHasExistingAdsAccountsRef.current === true &&
		initHasExistingMCAccountsRef.current === false;
	const shouldCreateBothAccounts =
		! initHasExistingAdsAccountsRef.current &&
		! initHasExistingMCAccountsRef.current;
	const isCreatingAccounts =
		isCreatingAdsAccount || isCreatingMCAccount || isCreatingBothAccounts;

	useEffect( () => {
		if ( ! response && ! loading ) {
			return;
		}

		if ( isCreatingAdsAccount && ! loading ) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingAdsAccount: false,
				accountsCreated: true,
			} ) );
			return;
		}

		const mcAccountCreated = [ 200, 403, 406, 503 ].includes(
			response?.status
		);
		if ( isCreatingMCAccount && mcAccountCreated ) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingMCAccount: false,
				accountsCreated: true,
			} ) );
			return;
		}

		if ( isCreatingBothAccounts && mcAccountCreated && ! loading ) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingBothAccounts: false,
				accountsCreated: true,
			} ) );
		}
	}, [
		response,
		loading,
		isCreatingAdsAccount,
		isCreatingBothAccounts,
		isCreatingMCAccount,
	] );

	useEffect( () => {
		const handleCreation = async () => {
			if (
				! accountCreationChecksResolved ||
				isCreatingAccounts ||
				accountsCreated
			) {
				return;
			}

			if ( shouldCreateAdsAccount ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingAdsAccount: true,
				} ) );
				await upsertAdsAccount();
				return;
			}

			if ( shouldCreateMCAccount ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingMCAccount: true,
				} ) );
				await handleCreateAccount();
				return;
			}

			if ( shouldCreateBothAccounts ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingBothAccounts: true,
				} ) );
				await handleCreateAccount();
				await upsertAdsAccount();
			}
		};

		handleCreation();
	}, [
		accountCreationChecksResolved,
		isCreatingAccounts,
		shouldCreateAdsAccount,
		shouldCreateMCAccount,
		shouldCreateBothAccounts,
		handleCreateAccount,
		upsertAdsAccount,
		accountsCreated,
	] );

	return {
		accountCreationChecksResolved,
		isCreatingOnlyAdsAccount: isCreatingAdsAccount,
		isCreatingOnlyMCAccount: isCreatingMCAccount,
		isCreatingBothAccounts,
		isCreatingAccounts,
		accountsCreated,
		hasExistingMCAccount,
	};
};

export default useAutoCreateAdsMCAccounts;
